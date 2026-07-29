<?php
/**
 * Plugin Name: Trivia Nationals Announcements
 * Description: Admin-authored announcements with a public list page and a full-body email digest tool for attendees.
 * Version: 0.3.0
 * Author: Trivia Nationals
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

final class TN_Announcements {
    private const POST_TYPE = 'tn_announcement';
    private const LOG_OPTION = 'tn_announcements_log';
    private const LOG_LIMIT = 20;
    private const BATCH_TRANSIENT_PREFIX = 'tn_announcements_batch_';
    private const BATCH_TTL = HOUR_IN_SECONDS;
    // Kept deliberately small/slow: a brand-new sending domain with no track
    // record can trip Google's own outbound rate-limiting/reputation checks
    // if a large batch goes out in a tight burst — confirmed live on
    // 2026-07-29, where roughly half of a 182-recipient send (spread across
    // dozens of unrelated domains, not one flaky server) came back
    // "Transient Error" after being sent 10-at-a-time with only a 350ms gap.
    private const BATCH_SIZE = 2;
    private const BATCH_INTER_SEND_USLEEP = 500000; // 0.5s between individual sends within a batch
    private const NONCE_ACTION = 'tn_announcements_digest';
    private const REORDER_NONCE_ACTION = 'tn_announcements_reorder';
    private const CAPABILITY = 'manage_woocommerce';
    private const LAST_SENT_META = '_tn_announcement_last_sent';

    public static function init(): void {
        add_action('init', [self::class, 'register_post_type']);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [self::class, 'admin_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [self::class, 'render_admin_column'], 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', [self::class, 'sortable_columns']);
        add_action('pre_get_posts', [self::class, 'sort_by_last_sent']);

        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_reorder_assets']);

        add_action('wp_ajax_tn_announcements_preview', [self::class, 'ajax_preview']);
        add_action('wp_ajax_tn_announcements_prepare', [self::class, 'ajax_prepare']);
        add_action('wp_ajax_tn_announcements_prepare_manual', [self::class, 'ajax_prepare_manual']);
        add_action('wp_ajax_tn_announcements_send_batch', [self::class, 'ajax_send_batch']);
        add_action('wp_ajax_tn_announcements_test', [self::class, 'ajax_test']);
        add_action('wp_ajax_tn_announcements_save_order', [self::class, 'ajax_save_order']);

        // Public /announcements/ virtual page.
        add_action('wp', [self::class, 'maybe_unmask_404']);
        add_filter('document_title_parts', [self::class, 'filter_document_title']);
        add_filter('body_class', [self::class, 'filter_body_class']);
        add_action('template_redirect', [self::class, 'maybe_render_public_page'], 2);
    }

    // ─── Custom post type ───────────────────────────────────────────────────

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Announcements',
                'singular_name' => 'Announcement',
                'menu_name' => 'Announcements',
                'add_new_item' => 'Add New Announcement',
                'edit_item' => 'Edit Announcement',
                'search_items' => 'Search Announcements',
                'not_found' => 'No announcements found',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-megaphone',
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'supports' => ['title', 'editor', 'excerpt'],
            'rewrite' => false,
        ]);
    }

    public static function admin_columns(array $columns): array {
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new['tn_announcement_teaser'] = 'Teaser';
            }
        }
        $new['tn_announcement_last_sent'] = 'Last Sent';
        return $new;
    }

    public static function render_admin_column(string $column, int $post_id): void {
        if ($column === 'tn_announcement_teaser') {
            $excerpt = get_post_field('post_excerpt', $post_id);
            echo $excerpt ? esc_html(wp_trim_words((string) $excerpt, 12)) : '&mdash;';
            return;
        }
        if ($column === 'tn_announcement_last_sent') {
            $last_sent = get_post_meta($post_id, self::LAST_SENT_META, true);
            echo $last_sent ? esc_html(mysql2date('M j, Y g:ia', (string) $last_sent)) : 'Never';
        }
    }

    public static function sortable_columns(array $columns): array {
        $columns['tn_announcement_last_sent'] = 'tn_announcement_last_sent';
        return $columns;
    }

    public static function sort_by_last_sent(WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        if ($query->get('post_type') !== self::POST_TYPE) {
            return;
        }
        if ($query->get('orderby') !== 'tn_announcement_last_sent') {
            return;
        }
        $query->set('meta_key', self::LAST_SENT_META);
        $query->set('orderby', 'meta_value');
    }

    // ─── Admin menu ──────────────────────────────────────────────────────────

    public static function add_menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            'Send Digest',
            'Send Digest',
            self::CAPABILITY,
            'tn-announcements-digest',
            [self::class, 'render_digest_page']
        );
        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            'Reorder',
            'Reorder',
            'edit_posts',
            'tn-announcements-reorder',
            [self::class, 'render_reorder_page']
        );
    }

    public static function enqueue_reorder_assets(string $hook): void {
        if (strpos($hook, 'tn-announcements-reorder') === false) {
            return;
        }
        wp_enqueue_script('jquery-ui-sortable');
    }

    // ─── Product catalog (forked from Attendee Email) ──────────────────────

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

    // ─── Recipient discovery (forked from Attendee Email, + date filter) ────

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
     * True if $date falls within the optional [$from, $to] range (Y-m-d
     * strings, either side may be null for "no bound"). A null $date never
     * matches a range that has at least one bound set.
     */
    private static function date_in_range(?WC_DateTime $date, ?string $from, ?string $to): bool {
        if (!$from && !$to) {
            return true;
        }
        if (!$date) {
            return false;
        }
        $value = $date->date('Y-m-d');
        if ($from && $value < $from) {
            return false;
        }
        if ($to && $value > $to) {
            return false;
        }
        return true;
    }

    /**
     * @param int[] $product_ids
     * @return array<string,array{email:string,name:string,sources:array<int|string>}>
     */
    private static function collect_recipients(array $product_ids, bool $want_allocated, ?string $date_from, ?string $date_to): array {
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
                if (!self::date_in_range($order->get_date_created(), $date_from, $date_to)) {
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
                // Allocated tickets have no explicit "date added" meta; post_date
                // (their implicit creation timestamp) is the direct analog of an
                // order's date_created, so the same range filter applies to it too
                // rather than silently exempting allocated-ticket holders from a
                // date filter the admin explicitly set.
                $post_date = substr((string) $post->post_date, 0, 10);
                if ($date_from && $post_date < $date_from) {
                    continue;
                }
                if ($date_to && $post_date > $date_to) {
                    continue;
                }
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

    /** @return array{from:?string,to:?string} */
    private static function date_range_from_request(): array {
        $validate = static function ($value): ?string {
            $value = is_string($value) ? sanitize_text_field($value) : '';
            if ($value === '') {
                return null;
            }
            $date = DateTime::createFromFormat('Y-m-d', $value);
            return ($date && $date->format('Y-m-d') === $value) ? $value : null;
        };
        return [
            'from' => $validate(isset($_POST['date_from']) ? wp_unslash($_POST['date_from']) : ''),
            'to' => $validate(isset($_POST['date_to']) ? wp_unslash($_POST['date_to']) : ''),
        ];
    }

    private static function announcement_ids_from_request(): array {
        $raw = isset($_POST['announcement_ids']) ? (array) wp_unslash($_POST['announcement_ids']) : [];
        return array_values(array_unique(array_filter(array_map('absint', $raw))));
    }

    /** @param int[] $ids @return int[] only ids that are actually published announcements */
    private static function published_announcement_ids(array $ids): array {
        $out = [];
        foreach ($ids as $id) {
            $post = get_post($id);
            if ($post && $post->post_type === self::POST_TYPE && $post->post_status === 'publish') {
                $out[] = (int) $id;
            }
        }
        return $out;
    }

    // ─── Digest content ──────────────────────────────────────────────────────

    /** @param int[] $announcement_ids */
    private static function assemble_digest_html(array $announcement_ids): string {
        $sections = [];
        foreach ($announcement_ids as $id) {
            $post = get_post($id);
            if (!$post || $post->post_type !== self::POST_TYPE || $post->post_status !== 'publish') {
                continue;
            }
            // get_the_title() already runs through wptexturize/convert_chars, which encode
            // entities like &#038; for HTML display — wrapping in esc_html() here would
            // double-encode them (visibly showing "&#038;" instead of "&"), so echo as-is.
            $section = '<h2 style="margin:24px 0 8px;color:#17406f;">' . get_the_title($post) . '</h2>';
            $teaser = trim((string) $post->post_excerpt);
            if ($teaser !== '') {
                $section .= '<p style="font-style:italic;color:#555;margin:0 0 12px;">' . esc_html($teaser) . '</p>';
            }
            $section .= apply_filters('the_content', $post->post_content);
            $sections[] = $section;
        }
        if (!$sections) {
            return '';
        }
        $html = '<div style="font-family:Arial,sans-serif;color:#222;line-height:1.5;">';
        $html .= '<h1 style="margin:0 0 20px;color:#17406f;">Trivia Nationals 2026</h1>';
        $html .= implode('<hr style="margin:24px 0;border:none;border-top:1px solid #ddd;">', $sections);
        $html .= '<hr style="margin:24px 0;border:none;border-top:1px solid #ddd;">';
        $html .= '<p style="font-size:14px;"><a href="' . esc_url(home_url('/announcements/')) . '">Read all announcements on the News &amp; Notes page &rarr;</a></p>';
        $html .= '</div>';
        return $html;
    }

    /** @param int[] $announcement_ids */
    private static function digest_subject(array $announcement_ids): string {
        $titles = [];
        foreach ($announcement_ids as $id) {
            $post = get_post($id);
            if ($post && $post->post_type === self::POST_TYPE) {
                // The email subject is plain text, not HTML, so decode any entities
                // get_the_title() introduced (e.g. "&#038;" back to a literal "&").
                $titles[] = wp_specialchars_decode(get_the_title($post), ENT_QUOTES);
            }
        }
        if (!$titles) {
            return 'Trivia Nationals Announcements';
        }
        $extra = count($titles) - 1;
        $suffix = $extra > 0 ? sprintf(' + %d more', $extra) : '';
        return $titles[0] . $suffix . " \xe2\x80\x94 Trivia Nationals Announcements";
    }

    private static function send_to(string $email, string $subject, string $html): bool {
        if (function_exists('tn_tde_send_signup_email')) {
            return (bool) tn_tde_send_signup_email($email, $subject, $html);
        }
        return wp_mail($email, $subject, $html, [
            'From: Trivia Nationals <info@trivianationals.org>',
            'Reply-To: Trivia Nationals <info@trivianationals.org>',
            'Content-Type: text/html; charset=UTF-8',
        ]);
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
        $range = self::date_range_from_request();
        $recipients = self::collect_recipients($product_ids, $want_allocated, $range['from'], $range['to']);
        wp_send_json_success(self::counts_for($recipients, $product_ids));
    }

    public static function ajax_prepare(): void {
        self::guard_ajax();
        $published_ids = self::published_announcement_ids(self::announcement_ids_from_request());
        if (!$published_ids) {
            wp_send_json_error(['message' => 'Select at least one published announcement to send.']);
        }

        $product_ids = self::product_ids_from_request();
        $want_allocated = self::want_allocated_from_request();
        $range = self::date_range_from_request();
        $recipients = self::collect_recipients($product_ids, $want_allocated, $range['from'], $range['to']);
        if (!$recipients) {
            wp_send_json_error(['message' => 'No matching attendees were found.']);
        }

        $counts = self::counts_for($recipients, $product_ids);
        $target_names = array_map(static fn(array $p): string => $p['name'], array_filter($counts['products'], static fn(array $p): bool => $p['count'] > 0));
        if ($counts['allocated'] > 0) {
            $target_names[] = 'Allocated tickets';
        }

        $titles = array_filter(array_map(static function ($id) {
            $post = get_post($id);
            // Log field is plain text, decode any entities get_the_title() introduced.
            return $post ? wp_specialchars_decode(get_the_title($post), ENT_QUOTES) : '';
        }, $published_ids));

        $batch_id = wp_generate_password(20, false, false);
        set_transient(self::BATCH_TRANSIENT_PREFIX . $batch_id, [
            'subject' => self::digest_subject($published_ids),
            'body' => self::assemble_digest_html($published_ids),
            'recipients' => array_values($recipients),
            'targets' => implode(', ', $target_names),
            'announcement_ids' => $published_ids,
            'announcement_titles' => implode(', ', $titles),
            'sent' => 0,
            'failed' => 0,
            'next_offset' => 0,
        ], self::BATCH_TTL);

        wp_send_json_success([
            'batch_id' => $batch_id,
            'counts' => $counts,
        ]);
    }

    /**
     * Same as ajax_prepare(), but the recipient list is a manually pasted set
     * of addresses instead of the WooCommerce-order-derived audience — for
     * resending to specific addresses (e.g. ones a prior send failed to
     * reach), reusing the exact same digest content and throttled batching.
     */
    public static function ajax_prepare_manual(): void {
        self::guard_ajax();
        $published_ids = self::published_announcement_ids(self::announcement_ids_from_request());
        if (!$published_ids) {
            wp_send_json_error(['message' => 'Select at least one published announcement to send.']);
        }

        $raw = isset($_POST['manual_emails']) ? (string) wp_unslash($_POST['manual_emails']) : '';
        $candidates = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $emails = [];
        foreach ((array) $candidates as $candidate) {
            $email = strtolower(trim(sanitize_text_field($candidate)));
            if (is_email($email)) {
                $emails[$email] = true;
            }
        }
        $emails = array_keys($emails);
        if (!$emails) {
            wp_send_json_error(['message' => 'Enter at least one valid email address.']);
        }

        $recipients = array_map(static fn(string $email): array => [
            'email' => $email,
            'name' => 'there',
            'sources' => ['manual'],
        ], $emails);

        $titles = array_filter(array_map(static function ($id) {
            $post = get_post($id);
            return $post ? wp_specialchars_decode(get_the_title($post), ENT_QUOTES) : '';
        }, $published_ids));

        $batch_id = wp_generate_password(20, false, false);
        set_transient(self::BATCH_TRANSIENT_PREFIX . $batch_id, [
            'subject' => self::digest_subject($published_ids),
            'body' => self::assemble_digest_html($published_ids),
            'recipients' => $recipients,
            'targets' => sprintf('Manual list (%d address%s)', count($emails), count($emails) === 1 ? '' : 'es'),
            'announcement_ids' => $published_ids,
            'announcement_titles' => implode(', ', $titles),
            'sent' => 0,
            'failed' => 0,
            'next_offset' => 0,
        ], self::BATCH_TTL);

        wp_send_json_success([
            'batch_id' => $batch_id,
            'total' => count($emails),
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
        foreach ($slice as $index => $recipient) {
            $ok = self::send_to($recipient['email'], $batch['subject'], $batch['body']);
            if ($ok) {
                $batch['sent']++;
            } else {
                $batch['failed']++;
            }
            $batch['next_offset']++;
            // Persist after every recipient so a retried request continues instead of resending.
            set_transient($key, $batch, self::BATCH_TTL);
            if ($index < count($slice) - 1) {
                usleep(self::BATCH_INTER_SEND_USLEEP);
            }
        }

        $next_offset = (int) $batch['next_offset'];
        $total = count($batch['recipients']);
        $done = $next_offset >= $total;

        if ($done) {
            delete_transient($key);
            self::append_log($batch['announcement_titles'] ?? '', $batch['targets'] ?? '', $total, $batch['sent'], $batch['failed']);
            self::mark_announcements_sent($batch['announcement_ids'] ?? []);
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
        $published_ids = self::published_announcement_ids(self::announcement_ids_from_request());
        if (!$published_ids) {
            wp_send_json_error(['message' => 'Select at least one published announcement first.']);
        }
        $user = wp_get_current_user();
        if (!$user || !is_email($user->user_email)) {
            wp_send_json_error(['message' => 'Your account has no usable email address.']);
        }
        $ok = self::send_to($user->user_email, '[TEST] ' . self::digest_subject($published_ids), self::assemble_digest_html($published_ids));
        if ($ok) {
            wp_send_json_success(['message' => 'Test email sent to ' . $user->user_email . '.']);
        }
        wp_send_json_error(['message' => 'The test email could not be sent.']);
    }

    // ─── Sent tracking ───────────────────────────────────────────────────────

    private static function append_log(string $announcement_titles, string $targets, int $total, int $sent, int $failed): void {
        $log = get_option(self::LOG_OPTION, []);
        if (!is_array($log)) {
            $log = [];
        }
        $log[] = [
            'time' => current_time('mysql'),
            'user' => wp_get_current_user()->display_name,
            'announcements' => $announcement_titles,
            'targets' => $targets,
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
        ];
        $log = array_slice($log, -self::LOG_LIMIT);
        update_option(self::LOG_OPTION, $log, false);
    }

    /** @param int[] $announcement_ids */
    private static function mark_announcements_sent(array $announcement_ids): void {
        $now = current_time('mysql');
        foreach ($announcement_ids as $id) {
            update_post_meta((int) $id, self::LAST_SENT_META, $now);
        }
    }

    // ─── Admin: Send Digest page ─────────────────────────────────────────────

    public static function render_digest_page(): void {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }
        $nonce = wp_create_nonce(self::NONCE_ACTION);
        $log = array_reverse(get_option(self::LOG_OPTION, []));
        $products = self::catalog_products();
        $announcements = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
            'no_found_rows' => true,
        ]);
        ?>
        <div class="wrap">
            <h1>Send Digest</h1>
            <p class="description">Select one or more published announcements below, choose who should receive them, and send the full content as one combined email. Emails are sent one attendee at a time through the same delivery path as ticket and signup emails.</p>

            <div class="card" style="max-width:900px;margin-top:16px;padding:20px 24px;">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Announcements</th>
                        <td>
                            <?php if (!$announcements): ?>
                                <p class="description">No published announcements yet. <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . self::POST_TYPE)); ?>">Add one</a> first.</p>
                            <?php else: ?>
                                <p>
                                    <a href="#" id="tn_an_select_all">Select all</a> &middot;
                                    <a href="#" id="tn_an_select_none">Select none</a>
                                </p>
                                <div style="max-height:280px;overflow:auto;border:1px solid #dcdcde;padding:8px 12px;background:#fff;">
                                    <?php foreach ($announcements as $announcement):
                                        $last_sent = get_post_meta($announcement->ID, self::LAST_SENT_META, true);
                                        $teaser = trim((string) $announcement->post_excerpt);
                                        ?>
                                        <label style="display:block;padding:6px 0;border-bottom:1px solid #f0f0f1;">
                                            <input type="checkbox" class="tn_an_announcement_cb" value="<?php echo esc_attr((string) $announcement->ID); ?>">
                                            <strong><?php echo get_the_title($announcement); ?></strong>
                                            <?php if ($teaser): ?><br><span style="color:#666;margin-left:24px;"><?php echo esc_html(wp_trim_words($teaser, 16)); ?></span><?php endif; ?>
                                            <br><span style="color:#999;font-size:12px;margin-left:24px;">
                                                <?php echo $last_sent ? 'Last sent ' . esc_html(mysql2date('M j, Y g:ia', (string) $last_sent)) : 'Never sent'; ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Products</th>
                        <td>
                            <?php if (!$products): ?>
                                <p class="description">No published WooCommerce products were found.</p>
                            <?php else: ?>
                                <p>
                                    <a href="#" id="tn_an_product_select_all">Select all</a> &middot;
                                    <a href="#" id="tn_an_product_select_none">Select none</a>
                                </p>
                                <div style="max-height:240px;overflow:auto;border:1px solid #dcdcde;padding:8px 12px;background:#fff;">
                                    <?php foreach ($products as $product): ?>
                                        <label style="display:block;padding:2px 0;">
                                            <input type="checkbox" class="tn_an_product_cb" value="<?php echo esc_attr((string) $product['id']); ?>">
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
                            <label><input type="checkbox" id="tn_an_include_allocated" checked> Include allocated ticket holders</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Narrow by ticket order date</th>
                        <td>
                            <label>From <input type="date" id="tn_an_date_from"></label>
                            &nbsp;&nbsp;
                            <label>Through <input type="date" id="tn_an_date_to"></label>
                            <p class="description">Optional &mdash; only include attendees whose order (or allocated ticket) falls in this range. Leave blank for no limit.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Recipients</th>
                        <td>
                            <p><button type="button" class="button" id="tn_an_preview_btn">Check recipient count</button></p>
                            <div id="tn_an_preview_result"></div>
                        </td>
                    </tr>
                </table>

                <p>
                    <label>
                        <input type="checkbox" id="tn_an_send_confirm">
                        I confirm the selected announcements and recipient preview are correct.
                    </label>
                </p>
                <p>
                    <button type="button" class="button" id="tn_an_test_btn">Send test email to me</button>
                    <button type="button" class="button button-primary" id="tn_an_send_btn">Send digest</button>
                    <button type="button" class="button" id="tn_an_resume_btn" style="display:none;">Resume interrupted send</button>
                </p>
                <div id="tn_an_progress" style="display:none;margin-top:12px;">
                    <progress id="tn_an_progress_bar" value="0" max="1" style="width:100%;"></progress>
                    <p id="tn_an_progress_text"></p>
                </div>
                <div id="tn_an_status" style="margin-top:8px;"></div>
            </div>

            <div class="card" style="max-width:900px;margin-top:20px;padding:20px 24px;">
                <h2 style="margin-top:0;">Send (or resend) to specific addresses</h2>
                <p class="description">Sends the announcement(s) checked above to exactly the addresses you list here, instead of the automatic audience &mdash; useful for resending to addresses a prior send didn&#8217;t reach (check Workspace&#8217;s Email Log Search to find who&#8217;s still missing one).</p>
                <p>
                    <label for="tn_an_manual_emails">Email addresses (one per line, or separated by commas)</label><br>
                    <textarea id="tn_an_manual_emails" rows="6" class="large-text code" placeholder="jane@example.com&#10;john@example.com"></textarea>
                </p>
                <p>
                    <button type="button" class="button button-primary" id="tn_an_manual_send_btn">Send to these addresses</button>
                    <button type="button" class="button" id="tn_an_manual_resume_btn" style="display:none;">Resume interrupted send</button>
                </p>
                <div id="tn_an_manual_progress" style="display:none;margin-top:12px;">
                    <progress id="tn_an_manual_progress_bar" value="0" max="1" style="width:100%;"></progress>
                    <p id="tn_an_manual_progress_text"></p>
                </div>
                <div id="tn_an_manual_status" style="margin-top:8px;"></div>
            </div>

            <h2 style="margin-top:28px;">Recent sends</h2>
            <table class="wp-list-table widefat fixed striped" style="max-width:1100px;">
                <thead><tr><th>Date</th><th>Sent by</th><th>Announcements</th><th>Targeted</th><th>Recipients</th><th>Failed</th></tr></thead>
                <tbody>
                <?php if (!$log): ?>
                    <tr><td colspan="6">No digests have been sent yet.</td></tr>
                <?php else: foreach ($log as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry['time'] ?? ''); ?></td>
                        <td><?php echo esc_html($entry['user'] ?? ''); ?></td>
                        <td><?php echo esc_html($entry['announcements'] ?? ''); ?></td>
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
            var storedBatchKey = 'tn_announcements_active_batch';

            function selection() {
                var announcementIds = Array.prototype.map.call(
                    document.querySelectorAll('.tn_an_announcement_cb:checked'),
                    function (cb) { return cb.value; }
                );
                var productIds = Array.prototype.map.call(
                    document.querySelectorAll('.tn_an_product_cb:checked'),
                    function (cb) { return cb.value; }
                );
                return {
                    announcement_ids: announcementIds,
                    product_ids: productIds,
                    allocated: document.getElementById('tn_an_include_allocated').checked ? '1' : '',
                    date_from: document.getElementById('tn_an_date_from').value,
                    date_to: document.getElementById('tn_an_date_to').value,
                };
            }

            document.getElementById('tn_an_select_all') && document.getElementById('tn_an_select_all').addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.tn_an_announcement_cb').forEach(function (cb) { cb.checked = true; });
            });
            document.getElementById('tn_an_select_none') && document.getElementById('tn_an_select_none').addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.tn_an_announcement_cb').forEach(function (cb) { cb.checked = false; });
            });
            document.getElementById('tn_an_product_select_all') && document.getElementById('tn_an_product_select_all').addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.tn_an_product_cb').forEach(function (cb) { cb.checked = true; });
            });
            document.getElementById('tn_an_product_select_none') && document.getElementById('tn_an_product_select_none').addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.tn_an_product_cb').forEach(function (cb) { cb.checked = false; });
            });

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

            // Both the audience-based "Send digest" flow and the manual
            // "Send to specific addresses" flow share the same prepare→batch
            // AJAX mechanics, just with their own DOM elements and localStorage
            // key so an interrupted send in one doesn't collide with the other.
            function makeSendController(idPrefix, storageKey) {
                var sendBtn = document.getElementById(idPrefix + '_send_btn');
                var resumeBtn = document.getElementById(idPrefix + '_resume_btn');
                var progress = document.getElementById(idPrefix + '_progress');
                var bar = document.getElementById(idPrefix + '_progress_bar');
                var text = document.getElementById(idPrefix + '_progress_text');
                var statusEl = document.getElementById(idPrefix + '_status');

                function setStatus(message, isError) {
                    statusEl.textContent = message;
                    statusEl.style.color = isError ? '#a00' : '#008a20';
                }

                function rememberBatch(batchId, total) {
                    try {
                        window.localStorage.setItem(storageKey, JSON.stringify({ batch_id: batchId, total: total }));
                    } catch (e) {}
                }

                function forgetBatch() {
                    try { window.localStorage.removeItem(storageKey); } catch (e) {}
                    resumeBtn.style.display = 'none';
                }

                function readRememberedBatch() {
                    try {
                        var value = JSON.parse(window.localStorage.getItem(storageKey) || 'null');
                        return value && value.batch_id ? value : null;
                    } catch (e) {
                        return null;
                    }
                }

                function runBatch(batchId, total) {
                    progress.style.display = 'block';
                    bar.max = total;
                    sendBtn.disabled = true;
                    resumeBtn.style.display = 'none';

                    function step() {
                        post('tn_announcements_send_batch', { batch_id: batchId }).then(function (res) {
                            if (!res.success) {
                                setStatus(res.data && res.data.message ? res.data.message : 'The send stopped early.', true);
                                sendBtn.disabled = false;
                                resumeBtn.style.display = 'inline-block';
                                return;
                            }
                            bar.value = res.data.sent + res.data.failed;
                            text.textContent = 'Sent ' + res.data.sent + ' of ' + res.data.total + (res.data.failed ? (' (' + res.data.failed + ' failed)') : '') + '…';
                            if (res.data.done) {
                                forgetBatch();
                                text.textContent = 'Done. Sent ' + res.data.sent + ' of ' + res.data.total + (res.data.failed ? (', ' + res.data.failed + ' failed') : '') + '.';
                                setStatus('Send complete.');
                                sendBtn.disabled = false;
                                window.location.reload();
                            } else {
                                // Deliberately slow: see BATCH_SIZE's comment in the PHP class for why.
                                window.setTimeout(step, 6000);
                            }
                        }).catch(function () {
                            setStatus('The browser lost contact with the server. Use “Resume interrupted send” to continue safely.', true);
                            sendBtn.disabled = false;
                            resumeBtn.style.display = 'inline-block';
                        });
                    }
                    step();
                }

                resumeBtn.addEventListener('click', function () {
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
                    resumeBtn.style.display = 'inline-block';
                }

                return { setStatus: setStatus, rememberBatch: rememberBatch, runBatch: runBatch };
            }

            var mainCtrl = makeSendController('tn_an', storedBatchKey);
            var manualCtrl = makeSendController('tn_an_manual', 'tn_announcements_manual_active_batch');

            function describeCounts(c) {
                var parts = c.products.filter(function (p) { return p.count > 0; }).map(function (p) {
                    return p.name + ': ' + p.count;
                });
                if (c.allocated > 0) parts.push('Allocated: ' + c.allocated);
                var detail = parts.length ? ' (' + parts.join(', ') + ')' : '';
                return c.total + ' unique attendee(s)' + detail;
            }

            document.getElementById('tn_an_preview_btn').addEventListener('click', function () {
                var out = document.getElementById('tn_an_preview_result');
                out.textContent = 'Checking…';
                post('tn_announcements_preview', selection()).then(function (res) {
                    if (!res.success) { out.textContent = res.data && res.data.message ? res.data.message : 'Could not check recipients.'; return; }
                    out.textContent = describeCounts(res.data);
                });
            });

            document.getElementById('tn_an_test_btn').addEventListener('click', function () {
                var sel = selection();
                if (!sel.announcement_ids.length) {
                    mainCtrl.setStatus('Select at least one announcement first.', true);
                    return;
                }
                mainCtrl.setStatus('Sending test…');
                post('tn_announcements_test', sel).then(function (res) {
                    mainCtrl.setStatus(res.success ? res.data.message : (res.data && res.data.message) || 'Could not send test email.', !res.success);
                });
            });

            document.getElementById('tn_an_send_btn').addEventListener('click', function () {
                var sel = selection();
                if (!sel.announcement_ids.length) {
                    mainCtrl.setStatus('Select at least one announcement first.', true);
                    return;
                }
                if (!sel.product_ids.length && !sel.allocated) {
                    mainCtrl.setStatus('Select at least one product or include allocated tickets.', true);
                    return;
                }
                if (!document.getElementById('tn_an_send_confirm').checked) {
                    mainCtrl.setStatus('Confirm the announcements and recipient preview before sending.', true);
                    return;
                }
                mainCtrl.setStatus('');
                post('tn_announcements_preview', sel).then(function (res) {
                    if (!res.success) { mainCtrl.setStatus(res.data && res.data.message ? res.data.message : 'Could not check recipients.', true); return; }
                    var total = res.data.total;
                    if (total < 1) { mainCtrl.setStatus('No matching attendees were found.', true); return; }
                    var estMinutes = Math.max(1, Math.round((total / 2) * 6.5 / 60));
                    if (!window.confirm('Send this digest to ' + total + ' attendee(s)? This cannot be undone.\n\nSent gradually (2 at a time, a few seconds apart) to avoid tripping spam/rate-limit protections — expect roughly ' + estMinutes + ' minute(s). Keep this tab open until it finishes.')) return;

                    document.getElementById('tn_an_progress').style.display = 'block';
                    document.getElementById('tn_an_progress_text').textContent = 'Preparing send…';
                    document.getElementById('tn_an_send_btn').disabled = true;

                    post('tn_announcements_prepare', sel).then(function (res) {
                        if (!res.success) {
                            mainCtrl.setStatus(res.data && res.data.message ? res.data.message : 'Could not start the send.', true);
                            document.getElementById('tn_an_send_btn').disabled = false;
                            return;
                        }
                        var batchId = res.data.batch_id;
                        mainCtrl.rememberBatch(batchId, total);
                        mainCtrl.runBatch(batchId, total);
                    });
                });
            });

            document.getElementById('tn_an_manual_send_btn').addEventListener('click', function () {
                var announcementIds = Array.prototype.map.call(
                    document.querySelectorAll('.tn_an_announcement_cb:checked'),
                    function (cb) { return cb.value; }
                );
                if (!announcementIds.length) {
                    manualCtrl.setStatus('Select at least one announcement above first.', true);
                    return;
                }
                var raw = document.getElementById('tn_an_manual_emails').value;
                var emails = raw.split(/[\s,]+/).map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean);
                var uniqueEmails = Array.from(new Set(emails));
                if (!uniqueEmails.length) {
                    manualCtrl.setStatus('Enter at least one email address.', true);
                    return;
                }
                if (!window.confirm('Send to these ' + uniqueEmails.length + ' address(es)? This cannot be undone.\n\n' + uniqueEmails.join(', '))) return;

                manualCtrl.setStatus('Preparing send…');
                document.getElementById('tn_an_manual_send_btn').disabled = true;
                post('tn_announcements_prepare_manual', { announcement_ids: announcementIds, manual_emails: uniqueEmails.join('\n') }).then(function (res) {
                    if (!res.success) {
                        manualCtrl.setStatus(res.data && res.data.message ? res.data.message : 'Could not start the send.', true);
                        document.getElementById('tn_an_manual_send_btn').disabled = false;
                        return;
                    }
                    var total = res.data.total;
                    manualCtrl.rememberBatch(res.data.batch_id, total);
                    manualCtrl.runBatch(res.data.batch_id, total);
                });
            });
        }());
        </script>
        <?php
    }

    // ─── Admin: Reorder page ─────────────────────────────────────────────────

    public static function render_reorder_page(): void {
        if (!current_user_can('edit_posts')) {
            return;
        }
        $nonce = wp_create_nonce(self::REORDER_NONCE_ACTION);
        $announcements = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => -1,
            'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
            'no_found_rows' => true,
        ]);
        ?>
        <div class="wrap">
            <h1>Reorder Announcements</h1>
            <p class="description">Drag announcements into the order you want them to appear on the public News &amp; Notes page and in the Send Digest checklist. This does not change Published/Draft status.</p>
            <?php if (!$announcements): ?>
                <p>No announcements yet.</p>
            <?php else: ?>
                <ul id="tn_an_reorder_list" style="max-width:640px;background:#fff;border:1px solid #dcdcde;margin:16px 0;padding:0;list-style:none;">
                    <?php foreach ($announcements as $announcement):
                        $is_draft = $announcement->post_status !== 'publish';
                        ?>
                        <li data-id="<?php echo esc_attr((string) $announcement->ID); ?>" style="padding:10px 14px;border-bottom:1px solid #f0f0f1;cursor:move;background:#fff;">
                            <span style="color:#999;margin-right:8px;" aria-hidden="true">&#9776;</span>
                            <strong><?php echo get_the_title($announcement); ?></strong>
                            <?php if ($is_draft): ?><span style="margin-left:8px;padding:1px 8px;border-radius:3px;background:#f0f0f1;color:#646970;font-size:11px;text-transform:uppercase;">Draft</span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p>
                    <button type="button" class="button button-primary" id="tn_an_save_order_btn" disabled>Save Order</button>
                    <span id="tn_an_reorder_status" style="margin-left:10px;"></span>
                </p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(function ($) {
            var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            var nonce = <?php echo wp_json_encode($nonce); ?>;
            var $list = $('#tn_an_reorder_list');
            var $btn = $('#tn_an_save_order_btn');
            var $status = $('#tn_an_reorder_status');

            if ($list.length && typeof $list.sortable === 'function') {
                $list.sortable({
                    axis: 'y',
                    update: function () {
                        $btn.prop('disabled', false);
                        $status.text('');
                    },
                });
            }

            $btn.on('click', function () {
                var ids = $list.children('li').map(function () {
                    return $(this).data('id');
                }).get();
                $btn.prop('disabled', true);
                $status.text('Saving…').css('color', '');
                var params = new URLSearchParams();
                params.append('action', 'tn_announcements_save_order');
                params.append('nonce', nonce);
                ids.forEach(function (id) { params.append('order[]', id); });
                fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: params })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.success) {
                            $status.text('Order saved.').css('color', '#008a20');
                        } else {
                            $status.text((res.data && res.data.message) || 'Could not save order.').css('color', '#a00');
                            $btn.prop('disabled', false);
                        }
                    })
                    .catch(function () {
                        $status.text('The browser lost contact with the server.').css('color', '#a00');
                        $btn.prop('disabled', false);
                    });
            });
        });
        </script>
        <?php
    }

    public static function ajax_save_order(): void {
        check_ajax_referer(self::REORDER_NONCE_ACTION, 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'You do not have permission to do this.'], 403);
        }
        $raw = isset($_POST['order']) ? (array) wp_unslash($_POST['order']) : [];
        $ids = array_values(array_unique(array_filter(array_map('absint', $raw))));
        if (!$ids) {
            wp_send_json_error(['message' => 'Nothing to save.']);
        }
        foreach ($ids as $index => $id) {
            $post = get_post($id);
            if (!$post || $post->post_type !== self::POST_TYPE) {
                continue;
            }
            wp_update_post(['ID' => $id, 'menu_order' => $index]);
        }
        wp_send_json_success(['message' => 'Order saved.']);
    }

    // ─── Public /announcements/ page ─────────────────────────────────────────

    public static function is_page_request(): bool {
        if (is_admin()) {
            return false;
        }
        $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        return strtolower($path) === 'announcements';
    }

    public static function maybe_unmask_404(): void {
        if (!self::is_page_request()) {
            return;
        }
        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        status_header(200);
    }

    public static function filter_document_title(array $parts): array {
        if (self::is_page_request()) {
            $parts['title'] = 'News & Notes';
        }
        return $parts;
    }

    public static function filter_body_class(array $classes): array {
        if (!self::is_page_request()) {
            return $classes;
        }
        $classes = array_diff($classes, ['error404']);
        $classes[] = 'tn-announcements-page';
        return array_values(array_unique($classes));
    }

    public static function maybe_render_public_page(): void {
        if (!self::is_page_request()) {
            return;
        }
        status_header(200);
        nocache_headers();
        self::render_public_page();
        exit;
    }

    private static function render_public_page(): void {
        $announcements = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
            'no_found_rows' => true,
        ]);
        get_header();
        ?>
        <main class="tn-signup-page">
            <style>
                body.tn-announcements-page .inner-main-title,
                body.tn-announcements-page .entry-header,
                body.tn-announcements-page .page-header {
                    display: none !important;
                }
                body.tn-announcements-page .site-content,
                body.tn-announcements-page .content-area,
                body.tn-announcements-page .site-main,
                body.tn-announcements-page .entry-content {
                    margin: 0 !important;
                    max-width: none !important;
                    padding: 0 !important;
                    width: 100% !important;
                }
                .tn-signup-page {
                    --tn-grid-bg: #0a0a14;
                    --tn-grid-panel: rgba(18,20,34,0.82);
                    --tn-grid-line: rgba(255,255,255,0.16);
                    --tn-grid-text: #f0f0f5;
                    --tn-grid-muted: #b7bdcf;
                    --tn-grid-cyan: #00e6ff;
                    color: var(--tn-grid-text);
                    background:
                        radial-gradient(circle at 18% 7%, rgba(0,230,255,0.18), transparent 24rem),
                        radial-gradient(circle at 82% 0%, rgba(255,62,165,0.16), transparent 25rem),
                        linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.012) 42%, rgba(0,0,0,0)),
                        var(--tn-grid-bg);
                    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                    margin-left: calc(50% - 50vw);
                    margin-right: calc(50% - 50vw);
                    max-width: none;
                    min-height: 100vh;
                    padding: clamp(2.5rem, 7vw, 6rem) clamp(1rem, 4vw, 4rem) clamp(2.5rem, 6vw, 5rem);
                    width: 100vw;
                }
                .tn-signup-page > * {
                    margin: 0 auto;
                    max-width: 1320px;
                }
                .tn-signup-nav {
                    align-items: center;
                    display: flex;
                    gap: 1rem;
                    justify-content: space-between;
                    margin-bottom: clamp(1.4rem, 3vw, 2.6rem);
                }
                .tn-signup-brand {
                    color: var(--tn-grid-text);
                    font-family: Outfit, Inter, sans-serif;
                    font-size: clamp(1rem, 1.5vw, 1.35rem);
                    font-weight: 900;
                    line-height: 1;
                    text-decoration: none;
                    text-transform: uppercase;
                }
                .tn-signup-nav nav {
                    align-items: center;
                    display: flex;
                    flex-wrap: wrap;
                    gap: clamp(0.75rem, 2vw, 1.5rem);
                    justify-content: flex-end;
                }
                .tn-signup-nav nav a {
                    color: var(--tn-grid-muted);
                    font-size: 0.84rem;
                    font-weight: 800;
                    text-decoration: none;
                    text-transform: uppercase;
                }
                .tn-signup-nav nav a:hover,
                .tn-signup-nav nav a[aria-current="page"] {
                    color: var(--tn-grid-cyan);
                }
                .tn-signup-page-inner {
                    width: min(980px, 100%);
                }
                .tn-signup-page h1 {
                    margin: 0 0 0.65rem;
                    color: var(--tn-grid-text);
                    font-family: Outfit, Inter, sans-serif;
                    font-size: clamp(2.6rem, 6vw, 5.2rem);
                    font-weight: 900;
                    letter-spacing: 0;
                    line-height: 0.9;
                    text-transform: uppercase;
                }
                .tn-signup-kicker {
                    color: var(--tn-grid-cyan);
                    font-size: clamp(0.8rem, 1.2vw, 1rem);
                    font-weight: 900;
                    letter-spacing: 0.12em;
                    margin: 0 0 0.55rem;
                    text-transform: uppercase;
                }
                .tn-signup-page-intro {
                    max-width: 46rem;
                    margin: 0 0 1.6rem;
                    color: var(--tn-grid-muted);
                    font-size: 1.05rem;
                    line-height: 1.6;
                }
                .tn-announcement-public {
                    border: 1px solid var(--tn-grid-line);
                    border-radius: 8px;
                    background: var(--tn-grid-panel);
                    padding: 1.4rem 1.6rem;
                    margin: 0 0 1.2rem;
                }
                .tn-announcement-public h2 {
                    margin: 0 0 0.4rem;
                    color: var(--tn-grid-text);
                    font-family: Outfit, Inter, sans-serif;
                    font-size: 1.5rem;
                    font-weight: 800;
                    text-transform: none;
                }
                .tn-announcement-public .tn-announcement-teaser {
                    color: var(--tn-grid-cyan);
                    font-style: italic;
                    font-size: 1rem;
                    margin: 0 0 0.8rem;
                }
                .tn-announcement-public .tn-announcement-body {
                    color: var(--tn-grid-text);
                    font-size: 1rem;
                    line-height: 1.65;
                }
                .tn-announcement-public .tn-announcement-body img {
                    max-width: 100%;
                    height: auto;
                    border-radius: 6px;
                }
                @media (max-width: 720px) {
                    .tn-signup-nav {
                        flex-direction: column;
                        align-items: flex-start;
                    }
                }
            </style>
            <div class="tn-signup-nav">
                <a class="tn-signup-brand" href="<?php echo esc_url(home_url('/')); ?>">Trivia Nationals 2026</a>
                <nav aria-label="News &amp; Notes page navigation">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                    <a href="<?php echo esc_url(home_url('/full-schedule/')); ?>">Full Schedule</a>
                    <a href="<?php echo esc_url(home_url('/event-signups/')); ?>">Signups</a>
                    <a href="<?php echo esc_url(home_url('/announcements/')); ?>" aria-current="page">News &amp; Notes</a>
                </nav>
            </div>
            <div class="tn-signup-page-inner">
                <p class="tn-signup-kicker">August 7 - 9, 2026 / Las Vegas</p>
                <h1>News &amp; Notes</h1>
                <?php if (!$announcements): ?>
                    <p class="tn-signup-page-intro">No announcements right now &mdash; check back soon.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement):
                        $teaser = trim((string) $announcement->post_excerpt);
                        ?>
                        <article class="tn-announcement-public">
                            <h2><?php echo get_the_title($announcement); ?></h2>
                            <?php if ($teaser): ?>
                                <p class="tn-announcement-teaser"><?php echo esc_html($teaser); ?></p>
                            <?php endif; ?>
                            <div class="tn-announcement-body"><?php echo apply_filters('the_content', $announcement->post_content); ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
        <?php
        get_footer();
    }
}

TN_Announcements::init();
