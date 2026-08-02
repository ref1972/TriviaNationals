<?php
/**
 * Plugin Name: Trivia Nationals Attendee Email
 * Description: Admin dashboard for emailing attendees who have purchased selected products (tickets, gift cards, etc.) or hold an allocated ticket.
 * Version: 0.4.0
 * Author: Trivia Nationals
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

final class TN_Attendee_Email {
    private const LOG_OPTION = 'tn_attendee_email_log';
    private const LOG_LIMIT = 20;
    private const BATCH_TRANSIENT_PREFIX = 'tn_attendee_email_batch_';
    private const BATCH_SIZE = 10;
    private const BATCH_TTL = HOUR_IN_SECONDS;
    private const NONCE_ACTION = 'tn_attendee_email';
    private const CAPABILITY = 'manage_woocommerce';

    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu'], 99);
        add_action('admin_footer', [self::class, 'add_trivia_nationals_menu_shortcut']);
        add_action('wp_ajax_tn_attendee_email_preview', [self::class, 'ajax_preview']);
        add_action('wp_ajax_tn_attendee_email_prepare', [self::class, 'ajax_prepare']);
        add_action('wp_ajax_tn_attendee_email_send_batch', [self::class, 'ajax_send_batch']);
        add_action('wp_ajax_tn_attendee_email_test', [self::class, 'ajax_test']);
    }

    public static function add_menu(): void {
        add_submenu_page(
            'woocommerce',
            'Email Attendees',
            'Email Attendees',
            self::CAPABILITY,
            'tn-email-attendees',
            [self::class, 'render_page']
        );
    }

    public static function add_trivia_nationals_menu_shortcut(): void {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }
        ?>
        <script>
        (function () {
            var parent = document.querySelector('#adminmenu a[href="edit.php?post_type=tn_tde_signup"]');
            var menuItem = parent && parent.closest('li.menu-top');
            var submenu = menuItem && menuItem.querySelector('.wp-submenu');
            if (!submenu || submenu.querySelector('a[href*="page=tn-email-attendees"]')) return;
            var item = document.createElement('li');
            item.innerHTML = '<a href="<?php echo esc_js(admin_url('admin.php?page=tn-email-attendees')); ?>">Email Attendees</a>';
            submenu.appendChild(item);
        }());
        </script>
        <?php
    }

    // ─── Product catalog ────────────────────────────────────────────────────

    /** @return array<int,array{id:int,name:string}> */
    private static function catalog_products(): array {
        if (!function_exists('wc_get_products')) {
            return [];
        }
        $products = wc_get_products([
            'limit' => -1,
            'status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        $out = [];
        foreach ($products as $product) {
            if (!$product instanceof WC_Product || $product->is_type('variation')) {
                continue;
            }
            $out[] = ['id' => $product->get_id(), 'name' => $product->get_name()];
        }
        return $out;
    }

    // ─── Recipient discovery ────────────────────────────────────────────────

    private static function first_name_for(WC_Order $order, ?WC_Order_Item_Product $item = null): string {
        if ($item) {
            foreach (['Preferred Name for Ticket/Badge', 'Preferred Name', 'preferred_name'] as $key) {
                $value = trim(wp_strip_all_tags((string) $item->get_meta($key, true)));
                if ($value !== '') {
                    $parts = preg_split('/\s+/', $value);
                    return sanitize_text_field($parts[0]);
                }
            }
        }
        $first = trim($order->get_billing_first_name());
        return $first !== '' ? sanitize_text_field($first) : 'there';
    }

    /**
     * @param int[] $product_ids
     * @return array<string,array{email:string,name:string,sources:array<int|string>}>
     */
    private static function collect_recipients(array $product_ids, bool $want_allocated): array {
        $recipients = [];
        $product_ids = array_values(array_unique(array_map('absint', $product_ids)));

        if ($product_ids && function_exists('wc_get_orders')) {
            $orders = wc_get_orders(['limit' => -1, 'return' => 'objects']);
            foreach ($orders as $order) {
                if (!$order instanceof WC_Order) {
                    continue;
                }
                if (!$order->is_paid() || in_array($order->get_status(), ['cancelled', 'refunded', 'failed'], true)) {
                    continue;
                }
                $email = strtolower(trim($order->get_billing_email()));
                if (!is_email($email)) {
                    continue;
                }
                foreach ($order->get_items('line_item') as $item) {
                    if (!$item instanceof WC_Order_Item_Product) {
                        continue;
                    }
                    $product_id = (int) $item->get_product_id();
                    if (!in_array($product_id, $product_ids, true)) {
                        continue;
                    }
                    if (!isset($recipients[$email])) {
                        $recipients[$email] = [
                            'email' => $email,
                            'name' => self::first_name_for($order, $item),
                            'sources' => [],
                        ];
                    }
                    if (!in_array($product_id, $recipients[$email]['sources'], true)) {
                        $recipients[$email]['sources'][] = $product_id;
                    }
                }
            }
        }

        if ($want_allocated && post_type_exists('tn_alloc_ticket')) {
            foreach (get_posts([
                'post_type' => 'tn_alloc_ticket',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'no_found_rows' => true,
            ]) as $post) {
                $email = strtolower(trim((string) get_post_meta($post->ID, '_tn_alloc_email', true)));
                if (!is_email($email)) {
                    continue;
                }
                if (!isset($recipients[$email])) {
                    $parts = preg_split('/\s+/', trim($post->post_title));
                    $recipients[$email] = [
                        'email' => $email,
                        'name' => $parts[0] !== '' ? sanitize_text_field($parts[0]) : 'there',
                        'sources' => [],
                    ];
                }
                if (!in_array('allocated', $recipients[$email]['sources'], true)) {
                    $recipients[$email]['sources'][] = 'allocated';
                }
            }
        }

        return $recipients;
    }

    /**
     * @param int[] $product_ids
     * @return array{total:int,allocated:int,products:array<int,array{id:int,name:string,count:int}>}
     */
    private static function counts_for(array $recipients, array $product_ids): array {
        $by_product = array_fill_keys($product_ids, 0);
        $allocated = 0;
        foreach ($recipients as $recipient) {
            foreach ($recipient['sources'] as $source) {
                if ($source === 'allocated') {
                    $allocated++;
                    continue;
                }
                if (isset($by_product[$source])) {
                    $by_product[$source]++;
                }
            }
        }
        $breakdown = [];
        foreach ($by_product as $id => $count) {
            $product = function_exists('wc_get_product') ? wc_get_product($id) : null;
            $breakdown[] = [
                'id' => $id,
                'name' => $product instanceof WC_Product ? $product->get_name() : ('Product #' . $id),
                'count' => $count,
            ];
        }
        return ['total' => count($recipients), 'allocated' => $allocated, 'products' => $breakdown];
    }

    private static function product_ids_from_request(): array {
        $raw = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : [];
        return array_values(array_unique(array_filter(array_map('absint', $raw))));
    }

    private static function want_allocated_from_request(): bool {
        return !empty($_POST['allocated']);
    }

    // ─── Sending ─────────────────────────────────────────────────────────────

    private static function render_email_html(string $body_html, string $first_name): string {
        $body_html = str_replace('{first_name}', esc_html($first_name), $body_html);
        $html = '<div style="font-family:Arial,sans-serif;color:#222;line-height:1.5;">';
        $html .= '<h2 style="margin:0 0 16px;color:#17406f;">Trivia Nationals 2026</h2>';
        $html .= $body_html;
        $html .= '</div>';
        return $html;
    }

    /** @return array{ok:bool,error:string,quota_exhausted:bool} */
    private static function send_to(string $email, string $subject, string $body_html, string $first_name): array {
        $subject = str_replace('{first_name}', $first_name, $subject);
        $html = self::render_email_html($body_html, $first_name);
        if (!function_exists('tn_tde_workspace_relay_request')) {
            return ['ok' => false, 'error' => 'Workspace relay integration is unavailable.', 'quota_exhausted' => false];
        }
        $result = tn_tde_workspace_relay_request([
            'action' => 'send_email',
            'to' => $email,
            'subject' => $subject,
            'html_body' => $html,
        ]);
        $error = empty($result['error']) ? '' : (string) $result['error'];
        return [
            'ok' => !empty($result['ok']),
            'error' => $error,
            'quota_exhausted' => !empty($result['quota_exhausted']) || stripos($error, 'quota') !== false || stripos($error, 'safety limit') !== false,
        ];
    }

    // ─── AJAX handlers ───────────────────────────────────────────────────────

    private static function guard_ajax(): void {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => 'You do not have permission to do this.'], 403);
        }
    }

    public static function ajax_preview(): void {
        self::guard_ajax();
        $product_ids = self::product_ids_from_request();
        $want_allocated = self::want_allocated_from_request();
        $recipients = self::collect_recipients($product_ids, $want_allocated);
        wp_send_json_success(self::counts_for($recipients, $product_ids));
    }

    public static function ajax_prepare(): void {
        self::guard_ajax();
        $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
        $body = isset($_POST['body']) ? wp_kses_post(wp_unslash($_POST['body'])) : '';
        if ($subject === '' || $body === '') {
            wp_send_json_error(['message' => 'Enter a subject and message body first.']);
        }
        $product_ids = self::product_ids_from_request();
        $want_allocated = self::want_allocated_from_request();
        $recipients = self::collect_recipients($product_ids, $want_allocated);
        if (!$recipients) {
            wp_send_json_error(['message' => 'No matching attendees were found.']);
        }
        $counts = self::counts_for($recipients, $product_ids);
        $target_names = array_map(static fn(array $p): string => $p['name'], array_filter($counts['products'], static fn(array $p): bool => $p['count'] > 0));
        if ($counts['allocated'] > 0) {
            $target_names[] = 'Allocated tickets';
        }
        $batch_id = wp_generate_password(20, false, false);
        set_transient(self::BATCH_TRANSIENT_PREFIX . $batch_id, [
            'subject' => $subject,
            'body' => $body,
            'recipients' => array_values($recipients),
            'targets' => implode(', ', $target_names),
            'sent' => 0,
            'failed' => 0,
            'next_offset' => 0,
        ], self::BATCH_TTL);
        wp_send_json_success([
            'batch_id' => $batch_id,
            'counts' => $counts,
        ]);
    }

    public static function ajax_send_batch(): void {
        self::guard_ajax();
        $batch_id = isset($_POST['batch_id']) ? sanitize_text_field(wp_unslash($_POST['batch_id'])) : '';
        $key = self::BATCH_TRANSIENT_PREFIX . $batch_id;
        $batch = get_transient($key);
        if (!is_array($batch) || empty($batch['recipients'])) {
            wp_send_json_error(['message' => 'This send has expired. Please start again.']);
        }

        $offset = max(0, absint($batch['next_offset'] ?? 0));
        $slice = array_slice($batch['recipients'], $offset, self::BATCH_SIZE);
        foreach ($slice as $recipient) {
            $result = self::send_to($recipient['email'], $batch['subject'], $batch['body'], $recipient['name']);
            if (!$result['ok']) {
                wp_send_json_error([
                    'message' => ($result['quota_exhausted'] ? 'The Workspace relay sending limit was reached.' : 'The Workspace relay did not accept the next message.') . ' The send paused without advancing that recipient: ' . $result['error'],
                ]);
            }
            $batch['sent']++;
            $batch['next_offset']++;
            // Persist after every recipient so a retried request continues instead of resending.
            set_transient($key, $batch, self::BATCH_TTL);
        }

        $next_offset = (int) $batch['next_offset'];
        $total = count($batch['recipients']);
        $done = $next_offset >= $total;

        if ($done) {
            delete_transient($key);
            self::append_log($batch['subject'], $batch['targets'] ?? '', $total, $batch['sent'], $batch['failed']);
        } else {
            set_transient($key, $batch, self::BATCH_TTL);
        }

        wp_send_json_success([
            'sent' => $batch['sent'],
            'failed' => $batch['failed'],
            'total' => $total,
            'next_offset' => $next_offset,
            'done' => $done,
        ]);
    }

    public static function ajax_test(): void {
        self::guard_ajax();
        $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
        $body = isset($_POST['body']) ? wp_kses_post(wp_unslash($_POST['body'])) : '';
        if ($subject === '' || $body === '') {
            wp_send_json_error(['message' => 'Enter a subject and message body first.']);
        }
        $user = wp_get_current_user();
        if (!$user || !is_email($user->user_email)) {
            wp_send_json_error(['message' => 'Your account has no usable email address.']);
        }
        $first_name = trim($user->first_name) !== '' ? trim($user->first_name) : 'there';
        $result = self::send_to($user->user_email, '[TEST] ' . $subject, $body, $first_name);
        if ($result['ok']) {
            wp_send_json_success(['message' => 'Test email sent to ' . $user->user_email . '.']);
        }
        wp_send_json_error(['message' => 'The test email could not be sent: ' . $result['error']]);
    }

    private static function append_log(string $subject, string $targets, int $total, int $sent, int $failed): void {
        $log = get_option(self::LOG_OPTION, []);
        if (!is_array($log)) {
            $log = [];
        }
        $log[] = [
            'time' => current_time('mysql'),
            'user' => wp_get_current_user()->display_name,
            'subject' => $subject,
            'targets' => $targets,
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
        ];
        $log = array_slice($log, -self::LOG_LIMIT);
        update_option(self::LOG_OPTION, $log, false);
    }

    // ─── Admin page ──────────────────────────────────────────────────────────

    public static function render_page(): void {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }
        $nonce = wp_create_nonce(self::NONCE_ACTION);
        $log = array_reverse(get_option(self::LOG_OPTION, []));
        $products = self::catalog_products();
        ?>
        <div class="wrap">
            <h1>Email Attendees</h1>
            <p class="description">Send an email update to everyone with a paid order for the product(s) you select below (allocated tickets can be included too). Emails are sent one attendee at a time through the same delivery path as ticket and signup emails.</p>

            <div class="card" style="max-width:820px;margin-top:16px;padding:20px 24px;">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="tn_ae_subject">Subject</label></th>
                        <td><input type="text" id="tn_ae_subject" class="large-text" placeholder="e.g. Trivia Nationals 2026 — Important Update"></td>
                    </tr>
                    <tr>
                        <th scope="row">Message</th>
                        <td>
                            <?php
                            wp_editor('', 'tn_ae_body', [
                                'textarea_name' => 'tn_ae_body',
                                'textarea_rows' => 12,
                                'media_buttons' => false,
                                'quicktags' => true,
                                'tinymce' => [
                                    'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo',
                                    'toolbar2' => '',
                                ],
                            ]);
                            ?>
                            <p class="description">Use <code>{first_name}</code> anywhere in the subject or message to insert each attendee's preferred first name.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Products</th>
                        <td>
                            <?php if (!$products): ?>
                                <p class="description">No published WooCommerce products were found.</p>
                            <?php else: ?>
                                <p>
                                    <a href="#" id="tn_ae_select_all">Select all</a> &middot;
                                    <a href="#" id="tn_ae_select_none">Select none</a>
                                </p>
                                <div style="max-height:240px;overflow:auto;border:1px solid #dcdcde;padding:8px 12px;background:#fff;">
                                    <?php foreach ($products as $product): ?>
                                        <label style="display:block;padding:2px 0;">
                                            <input type="checkbox" class="tn_ae_product_cb" value="<?php echo esc_attr((string) $product['id']); ?>">
                                            <?php echo esc_html($product['name']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <p class="description">Only paid, non-cancelled, non-refunded orders for the checked product(s) count as recipients.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Allocated tickets</th>
                        <td>
                            <label><input type="checkbox" id="tn_ae_include_allocated" checked> Include allocated ticket holders</label>
                            <p class="description">Someone who qualifies more than one way only receives one email.</p>
                            <p><button type="button" class="button" id="tn_ae_preview_btn">Check recipient count</button></p>
                            <div id="tn_ae_preview_result"></div>
                        </td>
                    </tr>
                </table>

                <p>
                    <label>
                        <input type="checkbox" id="tn_ae_send_confirm">
                        I confirm this is an appropriate attendee communication and the recipient preview is correct.
                    </label>
                </p>
                <p>
                    <button type="button" class="button" id="tn_ae_test_btn">Send test email to me</button>
                    <button type="button" class="button button-primary" id="tn_ae_send_btn">Send to matching attendees</button>
                    <button type="button" class="button" id="tn_ae_resume_btn" style="display:none;">Resume interrupted send</button>
                </p>
                <div id="tn_ae_progress" style="display:none;margin-top:12px;">
                    <progress id="tn_ae_progress_bar" value="0" max="1" style="width:100%;"></progress>
                    <p id="tn_ae_progress_text"></p>
                </div>
                <div id="tn_ae_status" style="margin-top:8px;"></div>
            </div>

            <h2 style="margin-top:28px;">Recent sends</h2>
            <table class="wp-list-table widefat fixed striped" style="max-width:1100px;">
                <thead><tr><th>Date</th><th>Sent by</th><th>Subject</th><th>Targeted</th><th>Recipients</th><th>Failed</th></tr></thead>
                <tbody>
                <?php if (!$log): ?>
                    <tr><td colspan="6">No attendee emails have been sent yet.</td></tr>
                <?php else: foreach ($log as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry['time'] ?? ''); ?></td>
                        <td><?php echo esc_html($entry['user'] ?? ''); ?></td>
                        <td><?php echo esc_html($entry['subject'] ?? ''); ?></td>
                        <td><?php echo esc_html($entry['targets'] ?? ''); ?></td>
                        <td><?php echo esc_html((string) ($entry['sent'] ?? 0)) . ' / ' . esc_html((string) ($entry['total'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($entry['failed'] ?? 0)); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <script>
        (function () {
            var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            var nonce = <?php echo wp_json_encode($nonce); ?>;
            var storedBatchKey = 'tn_attendee_email_active_batch';

            function selection() {
                var ids = Array.prototype.map.call(
                    document.querySelectorAll('.tn_ae_product_cb:checked'),
                    function (cb) { return cb.value; }
                );
                return {
                    product_ids: ids,
                    allocated: document.getElementById('tn_ae_include_allocated').checked ? '1' : '',
                };
            }

            document.getElementById('tn_ae_select_all') && document.getElementById('tn_ae_select_all').addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.tn_ae_product_cb').forEach(function (cb) { cb.checked = true; });
            });
            document.getElementById('tn_ae_select_none') && document.getElementById('tn_ae_select_none').addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.tn_ae_product_cb').forEach(function (cb) { cb.checked = false; });
            });

            function editorContent() {
                if (window.tinymce) {
                    var editor = window.tinymce.get('tn_ae_body');
                    if (editor && !editor.isHidden()) {
                        editor.save();
                    }
                }
                var el = document.getElementById('tn_ae_body');
                return el ? el.value : '';
            }

            function post(action, extra) {
                var params = new URLSearchParams();
                params.append('action', action);
                params.append('nonce', nonce);
                Object.keys(extra || {}).forEach(function (key) {
                    var val = extra[key];
                    if (Array.isArray(val)) {
                        val.forEach(function (v) { params.append(key + '[]', v); });
                    } else if (val !== undefined && val !== null) {
                        params.append(key, val);
                    }
                });
                return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: params })
                    .then(function (r) { return r.json(); });
            }

            function setStatus(message, isError) {
                var el = document.getElementById('tn_ae_status');
                el.textContent = message;
                el.style.color = isError ? '#a00' : '#008a20';
            }

            function rememberBatch(batchId, total) {
                try {
                    window.localStorage.setItem(storedBatchKey, JSON.stringify({ batch_id: batchId, total: total }));
                } catch (e) {}
            }

            function forgetBatch() {
                try { window.localStorage.removeItem(storedBatchKey); } catch (e) {}
                document.getElementById('tn_ae_resume_btn').style.display = 'none';
            }

            function readRememberedBatch() {
                try {
                    var value = JSON.parse(window.localStorage.getItem(storedBatchKey) || 'null');
                    return value && value.batch_id ? value : null;
                } catch (e) {
                    return null;
                }
            }

            function runBatch(batchId, total) {
                var progress = document.getElementById('tn_ae_progress');
                var bar = document.getElementById('tn_ae_progress_bar');
                var text = document.getElementById('tn_ae_progress_text');
                progress.style.display = 'block';
                bar.max = total;
                document.getElementById('tn_ae_send_btn').disabled = true;
                document.getElementById('tn_ae_resume_btn').style.display = 'none';

                function step() {
                    post('tn_attendee_email_send_batch', { batch_id: batchId }).then(function (res) {
                        if (!res.success) {
                            setStatus(res.data && res.data.message ? res.data.message : 'The send stopped early.', true);
                            document.getElementById('tn_ae_send_btn').disabled = false;
                            document.getElementById('tn_ae_resume_btn').style.display = 'inline-block';
                            return;
                        }
                        bar.value = res.data.sent + res.data.failed;
                        text.textContent = 'Sent ' + res.data.sent + ' of ' + res.data.total + (res.data.failed ? (' (' + res.data.failed + ' failed)') : '') + '…';
                        if (res.data.done) {
                            forgetBatch();
                            text.textContent = 'Done. Sent ' + res.data.sent + ' of ' + res.data.total + (res.data.failed ? (', ' + res.data.failed + ' failed') : '') + '.';
                            setStatus('Send complete.');
                            document.getElementById('tn_ae_send_btn').disabled = false;
                            window.location.reload();
                        } else {
                            window.setTimeout(step, 350);
                        }
                    }).catch(function () {
                        setStatus('The browser lost contact with the server. Use “Resume interrupted send” to continue safely.', true);
                        document.getElementById('tn_ae_send_btn').disabled = false;
                        document.getElementById('tn_ae_resume_btn').style.display = 'inline-block';
                    });
                }
                step();
            }

            function describeCounts(c) {
                var parts = c.products.filter(function (p) { return p.count > 0; }).map(function (p) {
                    return p.name + ': ' + p.count;
                });
                if (c.allocated > 0) parts.push('Allocated: ' + c.allocated);
                var detail = parts.length ? ' (' + parts.join(', ') + ')' : '';
                return c.total + ' unique attendee(s)' + detail;
            }

            document.getElementById('tn_ae_preview_btn').addEventListener('click', function () {
                var out = document.getElementById('tn_ae_preview_result');
                out.textContent = 'Checking…';
                post('tn_attendee_email_preview', selection()).then(function (res) {
                    if (!res.success) { out.textContent = res.data && res.data.message ? res.data.message : 'Could not check recipients.'; return; }
                    out.textContent = describeCounts(res.data);
                });
            });

            document.getElementById('tn_ae_test_btn').addEventListener('click', function () {
                var subject = document.getElementById('tn_ae_subject').value.trim();
                var body = editorContent();
                setStatus('Sending test…');
                post('tn_attendee_email_test', { subject: subject, body: body }).then(function (res) {
                    setStatus(res.success ? res.data.message : (res.data && res.data.message) || 'Could not send test email.', !res.success);
                });
            });

            document.getElementById('tn_ae_send_btn').addEventListener('click', function () {
                var subject = document.getElementById('tn_ae_subject').value.trim();
                var body = editorContent();
                if (!subject || !body) {
                    setStatus('Enter a subject and message body first.', true);
                    return;
                }
                var sel = selection();
                if (!sel.product_ids.length && !sel.allocated) {
                    setStatus('Select at least one product or include allocated tickets.', true);
                    return;
                }
                if (!document.getElementById('tn_ae_send_confirm').checked) {
                    setStatus('Confirm the communication and recipient preview before sending.', true);
                    return;
                }
                setStatus('');
                post('tn_attendee_email_preview', sel).then(function (res) {
                    if (!res.success) { setStatus(res.data && res.data.message ? res.data.message : 'Could not check recipients.', true); return; }
                    var total = res.data.total;
                    if (total < 1) { setStatus('No matching attendees were found.', true); return; }
                    if (!window.confirm('Send this email to ' + total + ' attendee(s)? This cannot be undone.')) return;

                    var progress = document.getElementById('tn_ae_progress');
                    var bar = document.getElementById('tn_ae_progress_bar');
                    var text = document.getElementById('tn_ae_progress_text');
                    progress.style.display = 'block';
                    bar.value = 0;
                    bar.max = total;
                    text.textContent = 'Preparing send…';
                    document.getElementById('tn_ae_send_btn').disabled = true;

                    post('tn_attendee_email_prepare', Object.assign({ subject: subject, body: body }, sel)).then(function (res) {
                        if (!res.success) {
                            setStatus(res.data && res.data.message ? res.data.message : 'Could not start the send.', true);
                            document.getElementById('tn_ae_send_btn').disabled = false;
                            return;
                        }
                        var batchId = res.data.batch_id;
                        rememberBatch(batchId, total);
                        runBatch(batchId, total);
                    });
                });
            });

            document.getElementById('tn_ae_resume_btn').addEventListener('click', function () {
                var batch = readRememberedBatch();
                if (!batch) {
                    forgetBatch();
                    setStatus('No interrupted send was found.', true);
                    return;
                }
                setStatus('Resuming send…');
                runBatch(batch.batch_id, batch.total);
            });

            if (readRememberedBatch()) {
                document.getElementById('tn_ae_resume_btn').style.display = 'inline-block';
            }
        }());
        </script>
        <?php
    }
}

TN_Attendee_Email::init();
