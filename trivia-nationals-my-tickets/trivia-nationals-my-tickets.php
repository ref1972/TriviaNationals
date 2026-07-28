<?php
/**
 * Plugin Name: Trivia Nationals My Tickets
 * Description: Passwordless electronic tickets backed by paid WooCommerce orders.
 * Version: 0.5.9
 * Author: Trivia Nationals
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

final class TN_My_Tickets {
    private const ELIGIBLE_PRODUCT_NAME = 'Trivia Nationals 2026 Ticket';
    private const OPTION_PAGE_ID = 'tn_tickets_page_id';
    private const OPTION_SCANNER_PAGE_ID = 'tn_tickets_scanner_page_id';
    private const OPTION_PRODUCT_IDS = 'tn_tickets_product_ids';
    private const OPTION_EVENT_DATES = 'tn_tickets_event_dates';
    private const OPTION_ALLOCATED_SEQUENCE = 'tn_allocated_ticket_sequence';
    private const ALLOCATED_POST_TYPE = 'tn_alloc_ticket';
    private const TOKEN_TTL = 1800;

    public static function init(): void {
        add_shortcode('tn_my_tickets', [self::class, 'render_shortcode']);
        add_shortcode('tn_ticket_scanner', [self::class, 'render_scanner_launcher']);
        add_action('init', [self::class, 'register_allocated_ticket_type']);
        add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
        add_action('admin_post_nopriv_tn_ticket_link', [self::class, 'handle_link_request']);
        add_action('admin_post_tn_ticket_link', [self::class, 'handle_link_request']);
        add_action('admin_post_nopriv_tn_ticket_email_copy', [self::class, 'handle_email_copy']);
        add_action('admin_post_tn_ticket_email_copy', [self::class, 'handle_email_copy']);
        add_action('admin_post_tn_ticket_check_in', [self::class, 'handle_check_in']);
        add_action('admin_post_tn_allocated_ticket_save', [self::class, 'handle_allocated_ticket_save']);
        add_action('admin_post_tn_save_ticket_names', [self::class, 'handle_save_ticket_names']);
        add_action('template_redirect', [self::class, 'require_staff_for_scanner'], 1);
        add_action('template_redirect', [self::class, 'serve_manifest'], 0);
        add_action('wp_head', [self::class, 'scanner_app_meta']);
        add_action('admin_menu', [self::class, 'add_settings_page'], 99);
        add_action('admin_footer', [self::class, 'add_allocated_ticket_menu_shortcut']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_filter('body_class', [self::class, 'body_classes']);
    }

    public static function activate(): void {
        if (get_option(self::OPTION_PRODUCT_IDS, '') === '') {
            update_option(self::OPTION_PRODUCT_IDS, '18347');
        }
        if (get_option(self::OPTION_EVENT_DATES, '') === '') {
            update_option(self::OPTION_EVENT_DATES, 'August 7–9, 2026');
        }

        self::ensure_page(self::OPTION_PAGE_ID, 'my-tickets', 'My Tickets', '[tn_my_tickets]');
        self::ensure_page(self::OPTION_SCANNER_PAGE_ID, 'ticket-check-in', 'Ticket Check-In', '[tn_ticket_scanner]');
    }

    private static function ensure_page(string $option, string $slug, string $title, string $content): void {
        $page_id = absint(get_option($option));
        if ($page_id && get_post($page_id)) return;
        $existing = get_page_by_path($slug);
        if ($existing instanceof WP_Post) {
            update_option($option, $existing->ID);
            return;
        }
        $page_id = wp_insert_post(['post_title' => $title, 'post_name' => $slug, 'post_content' => $content, 'post_status' => 'publish', 'post_type' => 'page']);
        if (!is_wp_error($page_id)) update_option($option, (int) $page_id);
    }

    public static function register_assets(): void {
        wp_register_style(
            'tn-my-tickets',
            plugin_dir_url(__FILE__) . 'assets/my-tickets.css',
            [],
            '0.5.3'
        );
        wp_register_script(
            'tn-ticket-qrcode',
            plugin_dir_url(__FILE__) . 'assets/qrcode.min.js',
            [],
            '1.0.0',
            true
        );
        wp_register_script('tn-html5-qrcode', plugin_dir_url(__FILE__) . 'assets/html5-qrcode.min.js', [], '2.3.8', true);
    }

    public static function register_allocated_ticket_type(): void {
        register_post_type(self::ALLOCATED_POST_TYPE, [
            'labels' => ['name' => 'Allocated Tickets', 'singular_name' => 'Allocated Ticket'],
            'public' => false,
            'show_ui' => false,
            'supports' => ['title'],
        ]);
    }

    public static function render_scanner_launcher(): string {
        wp_enqueue_style('tn-my-tickets');
        if (!current_user_can('manage_woocommerce')) {
            return self::render_scanner_message('Staff access required', 'Your account does not have permission to check in attendees.', false);
        }
        wp_enqueue_script('tn-html5-qrcode');
        wp_add_inline_script('tn-html5-qrcode', <<<'JS'
(function () {
    var start = document.getElementById('tn-scanner-start');
    var status = document.getElementById('tn-scanner-status');
    var reader = document.getElementById('tn-camera-reader');
    if (!start || !reader || typeof Html5Qrcode === 'undefined') return;
    var scanner = new Html5Qrcode(reader.id);
    var running = false;
    function setStatus(message, isError) {
        status.textContent = message;
        status.classList.toggle('is-error', !!isError);
    }
    function openTicket(decodedText) {
        try {
            var url = new URL(decodedText, window.location.href);
            if (url.origin !== window.location.origin || url.pathname.replace(/\/$/, '') !== '/my-tickets' || !url.searchParams.has('tn_ticket_scan')) {
                setStatus('That is not a Trivia Nationals ticket QR code.', true);
                return;
            }
            setStatus('Ticket found. Opening check-in…', false);
            scanner.stop().catch(function () {}).finally(function () { window.location.assign(url.href); });
        } catch (error) {
            setStatus('That QR code does not contain a valid ticket link.', true);
        }
    }
    start.addEventListener('click', function () {
        if (running) return;
        start.disabled = true;
        setStatus('Requesting camera access…', false);
        scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: function (width, height) { var size = Math.floor(Math.min(width, height) * 0.72); return { width: size, height: size }; }, aspectRatio: 1 },
            openTicket,
            function () {}
        ).then(function () {
            running = true;
            start.hidden = true;
            setStatus('Point the camera at the QR code on a ticket.', false);
        }).catch(function () {
            start.disabled = false;
            setStatus('Camera access was unavailable. Check browser permissions or use the phone Camera app.', true);
        });
    });
}());
JS
        );
        ob_start(); ?>
        <main class="tn-ticket-page tn-scanner-launcher">
            <div class="tn-ticket-page__inner tn-ticket-scanner">
                <p class="tn-ticket-kicker">Staff Check-In / Trivia Nationals 2026</p>
                <h1>Scan Tickets</h1>
                <section class="tn-mobile-scanner-card">
                    <div id="tn-camera-reader" class="tn-camera-reader"></div>
                    <p id="tn-scanner-status" class="tn-scanner-status" role="status">Ready to scan with your phone’s rear camera.</p>
                    <button id="tn-scanner-start" class="tn-scanner-start" type="button">Start Camera Scanner</button>
                </section>
                <details class="tn-home-screen-help">
                    <summary>Add this scanner to your phone’s Home Screen</summary>
                    <p><strong>iPhone:</strong> open this page in Safari, tap Share, then “Add to Home Screen.”</p>
                    <p><strong>Android:</strong> open this page in Chrome, open the menu, then tap “Add to Home screen” or “Install app.”</p>
                </details>
                <p class="tn-native-camera-help">You can also scan a ticket with the phone’s regular Camera app; its link will open the same secure check-in screen.</p>
                <?php echo self::render_checkin_roster(); ?>
            </div>
        </main>
        <?php return (string) ob_get_clean();
    }

    private static function render_checkin_roster(): string {
        $tickets = self::all_eligible_tickets();
        $checked_in = count(array_filter($tickets, static fn(array $ticket): bool => !empty($ticket['checkin'])));
        ob_start(); ?>
        <section class="tn-checkin-roster" aria-labelledby="tn-checkin-roster-title">
            <header>
                <div>
                    <p class="tn-ticket-kicker">Live Attendance</p>
                    <h2 id="tn-checkin-roster-title">Check-In Status</h2>
                </div>
                <p class="tn-checkin-total"><strong><?php echo esc_html((string) $checked_in); ?></strong> of <?php echo esc_html((string) count($tickets)); ?> checked in</p>
            </header>
            <div class="tn-checkin-progress" aria-label="<?php echo esc_attr($checked_in . ' of ' . count($tickets) . ' checked in'); ?>">
                <span style="width:<?php echo esc_attr(count($tickets) ? (string) (($checked_in / count($tickets)) * 100) : '0'); ?>%"></span>
            </div>
            <div class="tn-checkin-table-wrap">
                <table>
                    <thead><tr><th>Attendee</th><th>Ticket</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($tickets as $ticket) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($ticket['preferred_name']); ?></strong><small><?php echo esc_html($ticket['registered_name']); ?></small></td>
                            <td><?php echo esc_html($ticket['code']); ?></td>
                            <td><span class="tn-checkin-badge <?php echo $ticket['checkin'] ? 'is-checked-in' : 'is-pending'; ?>"><?php echo $ticket['checkin'] ? 'Checked in' : 'Not checked in'; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$tickets) : ?><tr><td colspan="3">No eligible tickets found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="tn-checkin-refresh"><a href="<?php echo esc_url(self::scanner_page_url()); ?>">Refresh status</a></p>
        </section>
        <?php return (string) ob_get_clean();
    }

    private static function all_eligible_tickets(): array {
        if (!function_exists('wc_get_orders')) return [];
        $orders = wc_get_orders(['limit' => -1, 'orderby' => 'date', 'order' => 'ASC']);
        $tickets = [];
        foreach ($orders as $order) {
            if (!$order instanceof WC_Order || !$order->is_paid() || in_array($order->get_status(), ['cancelled', 'refunded', 'failed'], true)) continue;
            foreach ($order->get_items('line_item') as $item_id => $item) {
                if (!$item instanceof WC_Order_Item_Product || !self::item_is_ticket($item)) continue;
                $quantity = max(1, (int) $item->get_quantity());
                $preferred_names = self::preferred_names_for_item($item, $order, $quantity);
                for ($position = 1; $position <= $quantity; $position++) {
                    $raw = (string) $item->get_meta('_tn_ticket_checked_in_' . $position, true);
                    $tickets[] = [
                        'registered_name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                        'preferred_name' => $preferred_names[$position - 1],
                        'code' => self::ticket_code((int) $order->get_id(), (int) $item_id, $position),
                        'checkin' => $raw !== '' && is_array(json_decode($raw, true)),
                    ];
                }
            }
        }
        foreach (self::allocated_tickets() as $allocated) {
            $tickets[] = [
                'registered_name' => $allocated['preferred_name'],
                'preferred_name' => $allocated['preferred_name'],
                'code' => $allocated['code'],
                'checkin' => !empty($allocated['checkin']),
            ];
        }
        usort($tickets, static fn(array $a, array $b): int => strcasecmp($a['preferred_name'], $b['preferred_name']));
        return $tickets;
    }

    /**
     * Canonical roster of registered ticket holders, one row per seat, for
     * other plugins (e.g. team roster assignment) to build attendee pickers
     * from. Unlike ticket_code(), the id here is not derived from wp_salt()
     * so it stays stable if WP secret keys are ever rotated.
     *
     * @return array<int,array{id:string,name:string,preferred_name:string,email:string}>
     */
    public static function attendee_roster(): array {
        $roster = [];
        if (function_exists('wc_get_orders')) {
            $orders = wc_get_orders(['limit' => -1, 'orderby' => 'date', 'order' => 'ASC']);
            foreach ($orders as $order) {
                if (!$order instanceof WC_Order || !$order->is_paid() || in_array($order->get_status(), ['cancelled', 'refunded', 'failed'], true)) continue;
                $email = strtolower(trim($order->get_billing_email()));
                foreach ($order->get_items('line_item') as $item_id => $item) {
                    if (!$item instanceof WC_Order_Item_Product || !self::item_is_ticket($item)) continue;
                    $quantity = max(1, (int) $item->get_quantity());
                    $preferred_names = self::preferred_names_for_item($item, $order, $quantity);
                    for ($position = 1; $position <= $quantity; $position++) {
                        $roster[] = [
                            'id' => 'wc:' . $order->get_id() . ':' . $item_id . ':' . $position,
                            'name' => $preferred_names[$position - 1],
                            'preferred_name' => $preferred_names[$position - 1],
                            'email' => $email,
                        ];
                    }
                }
            }
        }
        foreach (self::allocated_tickets() as $allocated) {
            $name = trim((string) $allocated['preferred_name']);
            $roster[] = [
                'id' => 'alloc:' . $allocated['id'],
                'name' => $name,
                'preferred_name' => $name,
                'email' => strtolower(trim((string) $allocated['email'])),
            ];
        }
        usort($roster, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
        return $roster;
    }

    public static function render_shortcode(): string {
        wp_enqueue_style('tn-my-tickets');
        $scan_token = isset($_GET['tn_ticket_scan'])
            ? sanitize_text_field(wp_unslash($_GET['tn_ticket_scan']))
            : '';
        if ($scan_token !== '') {
            return self::render_scanner($scan_token);
        }

        $token = isset($_GET['ticket_token'])
            ? sanitize_text_field(wp_unslash($_GET['ticket_token']))
            : '';

        if ($token !== '') {
            $email = self::email_for_token($token);
            if ($email !== '') {
                return self::render_ticket_hub($email, $token);
            }
            return self::render_request_form('That ticket link has expired. Request a new one below.', 'error');
        }

        $notice = isset($_GET['ticket_notice']) ? sanitize_key(wp_unslash($_GET['ticket_notice'])) : '';
        if ($notice === 'sent') {
            return self::render_request_form('If that email has eligible tickets, a secure link is on its way.', 'success');
        }
        if ($notice === 'emailed') {
            return self::render_request_form('A fresh ticket link has been emailed.', 'success');
        }
        return self::render_request_form();
    }

    private static function render_request_form(string $message = '', string $type = ''): string {
        ob_start();
        ?>
        <main class="tn-ticket-page">
            <?php echo self::page_navigation(); ?>
            <div class="tn-ticket-page__inner">
                <p class="tn-ticket-kicker">August 7 - 9, 2026 / Las Vegas</p>
                <h1>My Tickets</h1>
                <section class="tn-ticket-access" aria-labelledby="tn-ticket-access-title">
                    <img class="tn-ticket-access__logo" src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/ticket-logo.webp'); ?>" alt="Trivia Nationals 2026">
                    <div class="tn-ticket-access__body">
                        <h2 id="tn-ticket-access-title">Get your electronic tickets</h2>
                        <p class="tn-ticket-access__intro">Enter the email address used at checkout. We’ll send you a secure link to view and print every eligible ticket associated with it.</p>
                        <?php if ($message !== '') : ?>
                            <p class="tn-ticket-notice tn-ticket-notice--<?php echo esc_attr($type); ?>" role="status"><?php echo esc_html($message); ?></p>
                        <?php endif; ?>
                        <form class="tn-ticket-access__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="tn_ticket_link">
                            <?php wp_nonce_field('tn_ticket_link', 'tn_ticket_nonce'); ?>
                            <p>
                                <label for="tn-ticket-email">Contact Email</label>
                                <input id="tn-ticket-email" name="email" type="email" autocomplete="email" required>
                            </p>
                            <button type="submit">Email My Ticket Link</button>
                        </form>
                        <p class="tn-ticket-purchase-link">Still need to buy tickets? <a href="<?php echo esc_url(home_url('/product/trivia-nationals-2026-ticket/')); ?>">Purchase a Trivia Nationals 2026 ticket</a>.</p>
                        <p class="tn-ticket-access__help">For your privacy, this page won’t reveal whether an email address is in our records.</p>
                    </div>
                </section>
            </div>
        </main>
        <?php
        return (string) ob_get_clean();
    }

    public static function handle_link_request(): void {
        check_admin_referer('tn_ticket_link', 'tn_ticket_nonce');
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

        if (is_email($email) && !self::is_rate_limited($email)) {
            $tickets = self::eligible_tickets($email);
            if ($tickets) {
                self::send_ticket_link($email);
            }
        }

        wp_safe_redirect(add_query_arg('ticket_notice', 'sent', self::tickets_page_url()));
        exit;
    }

    public static function handle_email_copy(): void {
        $token = isset($_POST['ticket_token']) ? sanitize_text_field(wp_unslash($_POST['ticket_token'])) : '';
        if (!wp_verify_nonce(
            isset($_POST['tn_ticket_email_nonce']) ? sanitize_text_field(wp_unslash($_POST['tn_ticket_email_nonce'])) : '',
            'tn_ticket_email_copy'
        )) {
            wp_die('That request could not be verified.');
        }

        $email = self::email_for_token($token);
        if ($email !== '' && self::eligible_tickets($email)) {
            self::send_ticket_link($email);
        }
        wp_safe_redirect(add_query_arg('ticket_notice', 'emailed', self::tickets_page_url()));
        exit;
    }

    private static function send_ticket_link(string $email): bool {
        $token = self::create_token($email);
        $url = add_query_arg('ticket_token', rawurlencode($token), self::tickets_page_url());
        $subject = 'Your Trivia Nationals 2026 tickets';
        $html = '<div style="font-family:Arial,sans-serif;color:#222;line-height:1.5;">';
        $html .= '<h2 style="margin:0 0 16px;color:#17406f;">Trivia Nationals 2026 Tickets</h2>';
        $html .= '<p>Your secure electronic ticket link is ready.</p>';
        $html .= '<p><a href="' . esc_url($url) . '" style="display:inline-block;padding:11px 18px;border-radius:999px;background:#17406f;color:#fff;text-decoration:none;font-weight:bold;">View My Tickets</a></p>';
        $html .= '<p style="color:#666;font-size:13px;">This private link expires in 30 minutes. If you did not request it, you can ignore this email.</p>';
        $html .= '</div>';

        // Match the Event Signups delivery path: Gmail relay first, then a correctly branded wp_mail fallback.
        if (function_exists('tn_tde_send_signup_email')) {
            return (bool) tn_tde_send_signup_email($email, $subject, $html);
        }
        return wp_mail($email, $subject, $html, [
            'From: Trivia Nationals <info@trivianationals.org>',
            'Reply-To: Trivia Nationals <info@trivianationals.org>',
            'Content-Type: text/html; charset=UTF-8',
        ]);
    }

    private static function create_token(string $email): string {
        $token = bin2hex(random_bytes(32));
        set_transient('tn_ticket_' . hash('sha256', $token), strtolower($email), self::TOKEN_TTL);
        return $token;
    }

    private static function email_for_token(string $token): string {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return '';
        }
        $email = get_transient('tn_ticket_' . hash('sha256', $token));
        return is_string($email) && is_email($email) ? $email : '';
    }

    private static function is_rate_limited(string $email): bool {
        $email_key = 'tn_ticket_rate_email_' . hash('sha256', strtolower($email));
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $ip_key = 'tn_ticket_rate_ip_' . hash('sha256', $ip);
        if (get_transient($email_key) || get_transient($ip_key)) {
            return true;
        }
        set_transient($email_key, '1', MINUTE_IN_SECONDS);
        set_transient($ip_key, '1', 15);
        return false;
    }

    private static function eligible_tickets(string $email): array {
        if (!function_exists('wc_get_orders')) {
            return [];
        }

        $orders = wc_get_orders([
            'billing_email' => $email,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        $tickets = [];
        foreach ($orders as $order) {
            if (!$order instanceof WC_Order || !$order->is_paid() || in_array($order->get_status(), ['cancelled', 'refunded', 'failed'], true)) {
                continue;
            }
            foreach ($order->get_items('line_item') as $item_id => $item) {
                if (!self::item_is_ticket($item)) {
                    continue;
                }
                $quantity = max(1, (int) $item->get_quantity());
                $registered_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
                $preferred_names = self::preferred_names_for_item($item, $order, $quantity);
                for ($position = 1; $position <= $quantity; $position++) {
                    $tickets[] = [
                        'kind' => 'woocommerce',
                        'order' => $order,
                        'item' => $item,
                        'item_id' => (int) $item_id,
                        'position' => $position,
                        'quantity' => $quantity,
                        'registered_name' => $registered_name,
                        'preferred_name' => $preferred_names[$position - 1],
                        'title' => self::ELIGIBLE_PRODUCT_NAME,
                        'order_label' => '#' . $order->get_order_number(),
                        'code' => self::ticket_code((int) $order->get_id(), (int) $item_id, $position),
                        'scan_url' => self::scan_url((int) $order->get_id(), (int) $item_id, $position),
                    ];
                }
            }
        }
        foreach (self::allocated_tickets($email) as $allocated) {
            $tickets[] = [
                'kind' => 'allocated',
                'position' => 1,
                'quantity' => 1,
                'registered_name' => $allocated['preferred_name'],
                'preferred_name' => $allocated['preferred_name'],
                'title' => self::ELIGIBLE_PRODUCT_NAME,
                'order_label' => 'Allocated',
                'code' => $allocated['code'],
                'scan_url' => self::allocated_scan_url($allocated['id']),
            ];
        }
        return $tickets;
    }

    private static function allocated_tickets(string $email = ''): array {
        $query = ['post_type' => self::ALLOCATED_POST_TYPE, 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'];
        if ($email !== '') {
            $query['meta_query'] = [['key' => '_tn_alloc_email', 'value' => strtolower($email), 'compare' => '=']];
        }
        $tickets = [];
        foreach (get_posts($query) as $post) {
            $raw = (string) get_post_meta($post->ID, '_tn_alloc_checked_in', true);
            $checkin = $raw !== '' ? json_decode($raw, true) : null;
            $tickets[] = [
                'id' => (int) $post->ID,
                'preferred_name' => $post->post_title,
                'email' => (string) get_post_meta($post->ID, '_tn_alloc_email', true),
                'amount_paid' => (string) get_post_meta($post->ID, '_tn_alloc_amount_paid', true),
                'note' => (string) get_post_meta($post->ID, '_tn_alloc_note', true),
                'code' => (string) get_post_meta($post->ID, '_tn_alloc_code', true),
                'checkin' => is_array($checkin) ? $checkin : null,
            ];
        }
        return $tickets;
    }

    private static function item_is_ticket(WC_Order_Item_Product $item): bool {
        $name = trim(wp_strip_all_tags($item->get_name()));
        if ($name !== self::ELIGIBLE_PRODUCT_NAME) {
            return false;
        }

        $configured = self::configured_product_ids();
        $product_ids = array_filter([(int) $item->get_product_id(), (int) $item->get_variation_id()]);
        if ($configured) {
            return (bool) array_intersect($configured, $product_ids);
        }
        return true;
    }

    private static function configured_product_ids(): array {
        $value = (string) get_option(self::OPTION_PRODUCT_IDS, '');
        return array_values(array_unique(array_filter(array_map('absint', preg_split('/[\s,]+/', $value)))));
    }

    /**
     * All non-empty, trimmed string values stored under a meta key on an
     * order item. get_meta($key, false) returns an array of WC_Meta_Data
     * objects (not raw values) — unlike get_meta($key, true), which
     * unwraps to a single scalar. Handles both shapes defensively.
     *
     * @return string[]
     */
    private static function meta_values_for_key(WC_Order_Item_Product $item, string $key): array {
        return array_values(array_filter(array_map(
            static function ($v): string {
                $raw = $v instanceof WC_Meta_Data ? $v->value : $v;
                return trim(wp_strip_all_tags((string) $raw));
            },
            (array) $item->get_meta($key, false)
        )));
    }

    /**
     * Per-seat preferred names for a ticket line item, size == $quantity.
     * Reads ALL values stored under each candidate meta key (get_meta($key,
     * false) — WooCommerce meta supports multiple values per key), not just
     * the first, so a manually split multi-ticket line item (see order
     * 19505) resolves to distinct names per seat. A single shared value
     * (the common case) is repeated for every seat, same as before. Falls
     * back to the order's billing name only if no preferred-name meta
     * exists at all.
     *
     * @return string[]
     */
    private static function preferred_names_for_item(WC_Order_Item_Product $item, WC_Order $order, int $quantity): array {
        foreach (['Preferred Name for Ticket/Badge', 'Preferred Name', 'preferred_name'] as $key) {
            $values = self::meta_values_for_key($item, $key);
            if ($values) {
                $names = [];
                for ($i = 0; $i < $quantity; $i++) {
                    $names[] = $values[$i] ?? end($values);
                }
                return $names;
            }
        }
        $fallback = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        return array_fill(0, $quantity, $fallback);
    }

    /**
     * Which Preferred Name meta key to write to: whichever candidate key
     * currently holds a value, else the primary one.
     */
    private static function preferred_name_key_for_item(WC_Order_Item_Product $item): string {
        foreach (['Preferred Name for Ticket/Badge', 'Preferred Name', 'preferred_name'] as $key) {
            if (self::meta_values_for_key($item, $key)) {
                return $key;
            }
        }
        return 'Preferred Name for Ticket/Badge';
    }

    /**
     * Admin tool: view/edit the per-seat Preferred Name values for any
     * order's ticket line item(s). WooCommerce doesn't expose item meta
     * for editing once an order is paid, so this is the supported way to
     * fix a wrong or crammed-together name after the fact.
     */
    public static function render_ticket_names_page(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('You do not have permission to do this.');
        }
        $notice = isset($_GET['tn_notice']) ? sanitize_key(wp_unslash($_GET['tn_notice'])) : '';
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        ?>
        <div class="wrap">
            <h1>Ticket Names</h1>
            <p>Look up an order to view or correct the Preferred Name on each of its tickets. Only the Preferred Name field is touched — nothing else on the order changes.</p>
            <?php if ($notice === 'saved') : ?>
                <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
            <?php elseif ($notice === 'invalid') : ?>
                <div class="notice notice-error is-dismissible"><p>That could not be saved — the order/item could not be found, or the number of names didn't match the quantity.</p></div>
            <?php endif; ?>
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                <input type="hidden" name="page" value="tn-ticket-names">
                <p>
                    <label for="tn-order-id">Order ID</label>
                    <input type="number" id="tn-order-id" name="order_id" value="<?php echo esc_attr($order_id ?: ''); ?>" min="1">
                    <button type="submit" class="button">Load</button>
                </p>
            </form>
            <?php
            if ($order_id) {
                $order = wc_get_order($order_id);
                if (!$order instanceof WC_Order) {
                    echo '<p>Order ' . esc_html((string) $order_id) . ' was not found.</p>';
                } else {
                    $found = false;
                    foreach ($order->get_items('line_item') as $item_id => $item) {
                        if (!$item instanceof WC_Order_Item_Product || !self::item_is_ticket($item)) continue;
                        $found = true;
                        $quantity = max(1, (int) $item->get_quantity());
                        $names = self::preferred_names_for_item($item, $order, $quantity);
                        ?>
                        <div class="postbox" style="padding:16px;max-width:520px;margin-top:16px;">
                            <h2 style="margin-top:0;">Item #<?php echo esc_html((string) $item_id); ?> — quantity <?php echo esc_html((string) $quantity); ?></h2>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="tn_save_ticket_names">
                                <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                                <input type="hidden" name="item_id" value="<?php echo esc_attr($item_id); ?>">
                                <?php wp_nonce_field('tn_save_ticket_names_' . $order_id . '_' . $item_id); ?>
                                <?php foreach ($names as $i => $name) : ?>
                                    <p>
                                        <label for="tn-name-<?php echo esc_attr($i); ?>">Seat <?php echo esc_html((string) ($i + 1)); ?></label><br>
                                        <input type="text" id="tn-name-<?php echo esc_attr($i); ?>" name="names[]" value="<?php echo esc_attr($name); ?>" class="regular-text">
                                    </p>
                                <?php endforeach; ?>
                                <p><button type="submit" class="button button-primary">Save</button></p>
                            </form>
                        </div>
                        <?php
                    }
                    if (!$found) {
                        echo '<p>No eligible ticket line items found on that order.</p>';
                    }
                }
            }
            ?>
        </div>
        <?php
    }

    public static function handle_save_ticket_names(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('You do not have permission to do this.');
        }
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
        check_admin_referer('tn_save_ticket_names_' . $order_id . '_' . $item_id);

        $redirect = add_query_arg(['page' => 'tn-ticket-names', 'order_id' => $order_id], admin_url('admin.php'));

        $order = wc_get_order($order_id);
        $item = $order instanceof WC_Order ? $order->get_item($item_id) : false;
        if (!$order instanceof WC_Order || !$item instanceof WC_Order_Item_Product || !self::item_is_ticket($item)) {
            wp_safe_redirect(add_query_arg('tn_notice', 'invalid', $redirect));
            exit;
        }

        $quantity = max(1, (int) $item->get_quantity());
        $names = isset($_POST['names']) ? array_map('sanitize_text_field', (array) wp_unslash($_POST['names'])) : [];
        $names = array_map('trim', $names);
        if (count($names) !== $quantity) {
            wp_safe_redirect(add_query_arg('tn_notice', 'invalid', $redirect));
            exit;
        }

        $key = self::preferred_name_key_for_item($item);
        $item->delete_meta_data($key);
        foreach ($names as $name) {
            if ($name === '') continue;
            $item->add_meta_data($key, $name, false);
        }
        $item->save();

        wp_safe_redirect(add_query_arg('tn_notice', 'saved', $redirect));
        exit;
    }

    private static function ticket_code(int $order_id, int $item_id, int $position): string {
        $digest = strtoupper(hash_hmac('sha256', "{$order_id}:{$item_id}:{$position}", wp_salt('auth')));
        return 'TN26-' . substr($digest, 0, 4) . '-' . substr($digest, 4, 4);
    }

    private static function render_ticket_hub(string $email, string $token): string {
        $tickets = self::eligible_tickets($email);
        if ($tickets) {
            wp_enqueue_script('tn-ticket-qrcode');
            wp_add_inline_script('tn-ticket-qrcode', <<<'JS'
document.querySelectorAll('[data-tn-ticket-qr]').forEach(function (element) {
    if (!element.dataset.qrUrl || element.dataset.qrReady) return;
    element.dataset.qrReady = '1';
    new QRCode(element, {
        text: element.dataset.qrUrl,
        width: 104,
        height: 104,
        colorDark: '#07153d',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
});
JS
            );
        }
        ob_start();
        ?>
        <main class="tn-ticket-page">
            <?php echo self::page_navigation(); ?>
            <div class="tn-ticket-page__inner tn-ticket-hub">
                <p class="tn-ticket-kicker">August 7 - 9, 2026 / Las Vegas</p>
                <header class="tn-ticket-hub__header">
                    <div>
                        <h1>My Tickets</h1>
                        <p><?php echo esc_html(sprintf(_n('%d eligible ticket', '%d eligible tickets', count($tickets)), count($tickets))); ?></p>
                    </div>
                    <?php if ($tickets) : ?>
                        <button class="tn-ticket-print" type="button" onclick="window.print()">Print Tickets</button>
                    <?php endif; ?>
                </header>

            <?php if (!$tickets) : ?>
                <p class="tn-ticket-notice tn-ticket-notice--error">We couldn’t find a currently eligible 2026 ticket for this address. A refunded or cancelled order will not appear here.</p>
            <?php endif; ?>

            <div class="tn-ticket-list">
                <?php foreach ($tickets as $ticket) : ?>
                    <article class="tn-ticket">
                        <div class="tn-ticket__brand">
                            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/ticket-logo.webp'); ?>" alt="Trivia Nationals 2026">
                        </div>
                        <div class="tn-ticket__content">
                            <p class="tn-ticket-eyebrow">Official electronic ticket</p>
                            <h3><?php echo esc_html($ticket['title']); ?></h3>
                            <dl class="tn-ticket__details">
                                <div><dt>Registered name</dt><dd><?php echo esc_html($ticket['registered_name']); ?></dd></div>
                                <div><dt>Preferred name</dt><dd><?php echo esc_html($ticket['preferred_name']); ?></dd></div>
                                <div><dt>Dates</dt><dd><?php echo esc_html((string) get_option(self::OPTION_EVENT_DATES, 'August 7–9, 2026')); ?></dd></div>
                                <div><dt>Source</dt><dd><?php echo esc_html($ticket['order_label']); ?></dd></div>
                            </dl>
                        </div>
                        <div class="tn-ticket__stub">
                            <span>ADMIT ONE</span>
                            <div class="tn-ticket__qr" data-tn-ticket-qr data-qr-url="<?php echo esc_url($ticket['scan_url']); ?>" aria-label="Staff check-in QR code"></div>
                            <strong><?php echo esc_html($ticket['code']); ?></strong>
                            <?php if ($ticket['quantity'] > 1) : ?>
                                <small><?php echo esc_html($ticket['position'] . ' of ' . $ticket['quantity']); ?></small>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($tickets) : ?>
                <form class="tn-ticket-email-copy" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="tn_ticket_email_copy">
                    <input type="hidden" name="ticket_token" value="<?php echo esc_attr($token); ?>">
                    <?php wp_nonce_field('tn_ticket_email_copy', 'tn_ticket_email_nonce'); ?>
                    <button type="submit">Email me a fresh ticket link</button>
                </form>
            <?php endif; ?>
            <p class="tn-ticket-hub__privacy">This private link expires 30 minutes after it was requested. Don’t forward or share it.</p>
            </div>
        </main>
        <?php
        return (string) ob_get_clean();
    }

    private static function scan_url(int $order_id, int $item_id, int $position): string {
        $payload = self::base64url_encode("{$order_id}:{$item_id}:{$position}");
        $signature = hash_hmac('sha256', $payload, wp_salt('auth'));
        return add_query_arg('tn_ticket_scan', $payload . '.' . $signature, self::tickets_page_url());
    }

    private static function allocated_scan_url(int $post_id): string {
        $payload = self::base64url_encode('A:' . $post_id);
        $signature = hash_hmac('sha256', $payload, wp_salt('auth'));
        return add_query_arg('tn_ticket_scan', $payload . '.' . $signature, self::tickets_page_url());
    }

    private static function base64url_encode(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function scan_context(string $token): array {
        if (!preg_match('/^([A-Za-z0-9_-]+)\.([a-f0-9]{64})$/', $token, $matches)) {
            return ['valid' => false, 'reason' => 'This QR code is not recognized.'];
        }
        $payload = $matches[1];
        $expected = hash_hmac('sha256', $payload, wp_salt('auth'));
        if (!hash_equals($expected, $matches[2])) {
            return ['valid' => false, 'reason' => 'This QR code has an invalid signature.'];
        }
        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if (is_string($decoded) && preg_match('/^A:(\d+)$/', $decoded, $allocated_parts)) {
            $post_id = absint($allocated_parts[1]);
            $post = get_post($post_id);
            if (!$post instanceof WP_Post || $post->post_type !== self::ALLOCATED_POST_TYPE || $post->post_status !== 'publish') {
                return ['valid' => false, 'reason' => 'This allocated ticket no longer exists.'];
            }
            $checkin_raw = (string) get_post_meta($post_id, '_tn_alloc_checked_in', true);
            $checkin = $checkin_raw !== '' ? json_decode($checkin_raw, true) : null;
            return [
                'valid' => true,
                'kind' => 'allocated',
                'post_id' => $post_id,
                'registered_name' => $post->post_title,
                'preferred_name' => $post->post_title,
                'code' => (string) get_post_meta($post_id, '_tn_alloc_code', true),
                'source_label' => 'Allocated ticket',
                'checkin' => is_array($checkin) ? $checkin : null,
            ];
        }
        if (!is_string($decoded) || !preg_match('/^(\d+):(\d+):(\d+)$/', $decoded, $parts)) {
            return ['valid' => false, 'reason' => 'This QR code contains invalid ticket data.'];
        }

        $order_id = absint($parts[1]);
        $item_id = absint($parts[2]);
        $position = absint($parts[3]);
        $order = wc_get_order($order_id);
        $item = $order instanceof WC_Order ? $order->get_item($item_id) : false;
        if (!$order instanceof WC_Order || !$item instanceof WC_Order_Item_Product) {
            return ['valid' => false, 'reason' => 'The WooCommerce order or ticket item no longer exists.'];
        }
        if (!$order->is_paid() || in_array($order->get_status(), ['cancelled', 'refunded', 'failed'], true)) {
            return ['valid' => false, 'reason' => 'This order is no longer paid and eligible.'];
        }
        if (!self::item_is_ticket($item) || $position < 1 || $position > max(1, (int) $item->get_quantity())) {
            return ['valid' => false, 'reason' => 'This order item is not an eligible Trivia Nationals 2026 ticket.'];
        }

        $checkin_key = '_tn_ticket_checked_in_' . $position;
        $checkin_raw = (string) $item->get_meta($checkin_key, true);
        $checkin = $checkin_raw !== '' ? json_decode($checkin_raw, true) : null;
        $preferred_names = self::preferred_names_for_item($item, $order, max(1, (int) $item->get_quantity()));
        return [
            'valid' => true,
            'kind' => 'woocommerce',
            'order' => $order,
            'item' => $item,
            'item_id' => $item_id,
            'position' => $position,
            'registered_name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'preferred_name' => $preferred_names[$position - 1],
            'code' => self::ticket_code($order_id, $item_id, $position),
            'source_label' => '#' . $order->get_order_number(),
            'checkin_key' => $checkin_key,
            'checkin' => is_array($checkin) ? $checkin : null,
        ];
    }

    public static function require_staff_for_scanner(): void {
        if (empty($_GET['tn_ticket_scan']) && !self::is_scanner_page()) {
            return;
        }
        if (!is_user_logged_in()) {
            $current_url = home_url(wp_unslash($_SERVER['REQUEST_URI'] ?? '/my-tickets/'));
            wp_safe_redirect(wp_login_url($current_url));
            exit;
        }
    }

    private static function is_scanner_page(): bool {
        $page_id = absint(get_option(self::OPTION_SCANNER_PAGE_ID));
        return $page_id > 0 && is_page($page_id);
    }

    public static function serve_manifest(): void {
        if (!isset($_GET['tn_ticket_manifest'])) return;
        $manifest = [
            'name' => 'Trivia Nationals Check-In',
            'short_name' => 'TN Check-In',
            'start_url' => home_url('/ticket-check-in/'),
            'scope' => home_url('/'),
            'display' => 'standalone',
            'background_color' => '#0a0a14',
            'theme_color' => '#0a0a14',
        ];
        $icon = get_site_icon_url(512);
        if ($icon) $manifest['icons'] = [['src' => $icon, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable']];
        nocache_headers();
        header('Content-Type: application/manifest+json; charset=UTF-8');
        echo wp_json_encode($manifest, JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function scanner_app_meta(): void {
        if (!self::is_scanner_page()) return;
        echo '<link rel="manifest" href="' . esc_url(add_query_arg('tn_ticket_manifest', '1', home_url('/'))) . '">' . "\n";
        echo '<meta name="theme-color" content="#0a0a14">' . "\n";
        echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
        echo '<meta name="apple-mobile-web-app-title" content="TN Check-In">' . "\n";
    }

    private static function render_scanner(string $token): string {
        if (!current_user_can('manage_woocommerce')) {
            return self::render_scanner_message('Staff access required', 'Your account does not have permission to check in attendees.', false);
        }
        $context = self::scan_context($token);
        if (empty($context['valid'])) {
            return self::render_scanner_message('Invalid ticket', (string) ($context['reason'] ?? 'This ticket could not be validated.'), false);
        }

        $notice = isset($_GET['tn_checked_in']) ? sanitize_key(wp_unslash($_GET['tn_checked_in'])) : '';
        ob_start();
        ?>
        <main class="tn-ticket-page">
            <?php echo self::page_navigation(); ?>
            <div class="tn-ticket-page__inner tn-ticket-scanner">
                <p class="tn-ticket-kicker">Staff Check-In / Trivia Nationals 2026</p>
                <h1>Valid Ticket</h1>
                <?php if ($notice === 'success') : ?>
                    <p class="tn-ticket-notice tn-ticket-notice--success" role="status">Check-in recorded successfully.</p>
                <?php elseif ($notice === 'already') : ?>
                    <p class="tn-ticket-notice tn-ticket-notice--warning" role="status">This attendee was already checked in.</p>
                <?php endif; ?>
                <section class="tn-ticket-scan-card tn-ticket-scan-card--valid">
                    <p class="tn-ticket-scan-status">✓ Valid and paid</p>
                    <dl>
                        <div><dt>Registered name</dt><dd><?php echo esc_html($context['registered_name']); ?></dd></div>
                        <div><dt>Preferred name</dt><dd><?php echo esc_html($context['preferred_name']); ?></dd></div>
                        <div><dt>Ticket</dt><dd><?php echo esc_html($context['code']); ?></dd></div>
                        <div><dt>Source</dt><dd><?php echo esc_html($context['source_label']); ?></dd></div>
                    </dl>
                    <?php if ($context['checkin']) : ?>
                        <p class="tn-ticket-checked-in">Already checked in <?php echo esc_html(self::checkin_description($context['checkin'])); ?></p>
                    <?php else : ?>
                        <form class="tn-ticket-checkin-actions" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="tn_ticket_check_in">
                            <input type="hidden" name="tn_ticket_scan" value="<?php echo esc_attr($token); ?>">
                            <?php wp_nonce_field('tn_ticket_check_in_' . $token, 'tn_ticket_check_in_nonce'); ?>
                            <button type="submit">Check In Attendee</button>
                            <a class="tn-ticket-cancel" href="<?php echo esc_url(self::scanner_page_url()); ?>">Cancel</a>
                        </form>
                    <?php endif; ?>
                    <?php if ($context['checkin']) : ?><a class="tn-ticket-cancel tn-ticket-cancel--standalone" href="<?php echo esc_url(self::scanner_page_url()); ?>">Back to Scanner</a><?php endif; ?>
                </section>
            </div>
        </main>
        <?php
        return (string) ob_get_clean();
    }

    private static function render_scanner_message(string $title, string $message, bool $valid): string {
        ob_start();
        ?>
        <main class="tn-ticket-page">
            <?php echo self::page_navigation(); ?>
            <div class="tn-ticket-page__inner tn-ticket-scanner">
                <p class="tn-ticket-kicker">Staff Check-In / Trivia Nationals 2026</p>
                <h1><?php echo esc_html($title); ?></h1>
                <section class="tn-ticket-scan-card <?php echo $valid ? 'tn-ticket-scan-card--valid' : 'tn-ticket-scan-card--invalid'; ?>">
                    <p><?php echo esc_html($message); ?></p>
                </section>
            </div>
        </main>
        <?php
        return (string) ob_get_clean();
    }

    public static function handle_check_in(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('You do not have permission to check in attendees.');
        }
        $token = isset($_POST['tn_ticket_scan']) ? sanitize_text_field(wp_unslash($_POST['tn_ticket_scan'])) : '';
        check_admin_referer('tn_ticket_check_in_' . $token, 'tn_ticket_check_in_nonce');
        $context = self::scan_context($token);
        if (empty($context['valid'])) {
            wp_safe_redirect(add_query_arg('tn_ticket_scan', rawurlencode($token), self::tickets_page_url()));
            exit;
        }

        $result = 'already';
        if (empty($context['checkin'])) {
            $checkin = wp_json_encode([
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id(),
            ]);
            if (($context['kind'] ?? '') === 'allocated') {
                update_post_meta($context['post_id'], '_tn_alloc_checked_in', $checkin);
            } else {
                /** @var WC_Order_Item_Product $item */
                $item = $context['item'];
                $item->update_meta_data($context['checkin_key'], $checkin);
                $item->save();
            }
            $result = 'success';
        }
        wp_safe_redirect(add_query_arg([
            'tn_ticket_scan' => rawurlencode($token),
            'tn_checked_in' => $result,
        ], self::tickets_page_url()));
        exit;
    }

    private static function checkin_description(array $checkin): string {
        $timestamp = isset($checkin['timestamp']) ? strtotime((string) $checkin['timestamp']) : false;
        $user = !empty($checkin['user_id']) ? get_user_by('id', absint($checkin['user_id'])) : false;
        $description = $timestamp ? 'on ' . wp_date('F j, Y \a\t g:i a', $timestamp) : '';
        if ($user instanceof WP_User) {
            $description .= ($description !== '' ? ' by ' : 'by ') . $user->display_name;
        }
        return trim($description);
    }

    private static function page_navigation(): string {
        ob_start();
        ?>
        <div class="tn-ticket-nav">
            <a class="tn-ticket-brand" href="<?php echo esc_url(home_url('/')); ?>">Trivia Nationals 2026</a>
            <nav aria-label="Ticket page navigation">
                <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                <a href="<?php echo esc_url(home_url('/#schedule')); ?>">Schedule</a>
                <a href="<?php echo esc_url(home_url('/full-schedule/')); ?>">Full Schedule</a>
                <a href="<?php echo esc_url(home_url('/event-signups/')); ?>">Signups</a>
                <a href="<?php echo esc_url(self::tickets_page_url()); ?>" aria-current="page">Tickets</a>
            </nav>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public static function body_classes(array $classes): array {
        $page_id = absint(get_option(self::OPTION_PAGE_ID));
        if ($page_id && is_page($page_id)) {
            $classes[] = 'tn-my-tickets-page';
        }
        if (self::is_scanner_page()) {
            $classes[] = 'tn-my-tickets-page';
            $classes[] = 'tn-ticket-scanner-page';
        }
        return array_values(array_unique($classes));
    }

    private static function tickets_page_url(): string {
        $page_id = absint(get_option(self::OPTION_PAGE_ID));
        $url = $page_id ? get_permalink($page_id) : home_url('/my-tickets/');
        return $url ?: home_url('/my-tickets/');
    }

    private static function scanner_page_url(): string {
        $page_id = absint(get_option(self::OPTION_SCANNER_PAGE_ID));
        $url = $page_id ? get_permalink($page_id) : home_url('/ticket-check-in/');
        return $url ?: home_url('/ticket-check-in/');
    }

    public static function add_settings_page(): void {
        add_submenu_page(
            'woocommerce',
            'Allocated Tickets',
            'Allocated Tickets',
            'manage_woocommerce',
            'tn-allocated-tickets',
            [self::class, 'render_allocated_tickets_page']
        );
        add_submenu_page(
            'woocommerce',
            'Trivia Nationals Tickets',
            'TN Tickets',
            'manage_woocommerce',
            'tn-my-tickets',
            [self::class, 'render_settings_page']
        );
        add_submenu_page(
            'woocommerce',
            'Ticket Names',
            'Ticket Names',
            'manage_woocommerce',
            'tn-ticket-names',
            [self::class, 'render_ticket_names_page']
        );
    }

    public static function add_allocated_ticket_menu_shortcut(): void {
        if (!current_user_can('manage_woocommerce')) return;
        ?>
        <script>
        (function () {
            var parent = document.querySelector('#adminmenu a[href="edit.php?post_type=tn_tde_signup"]');
            var submenu = parent && parent.closest('li.menu-top') && parent.closest('li.menu-top').querySelector('.wp-submenu');
            if (!submenu || submenu.querySelector('a[href*="page=tn-allocated-tickets"]')) return;
            var item = document.createElement('li');
            item.innerHTML = '<a href="<?php echo esc_js(self::allocated_admin_url()); ?>">Allocated Tickets</a>';
            submenu.appendChild(item);
        }());
        </script>
        <?php
    }

    public static function handle_allocated_ticket_save(): void {
        if (!current_user_can('manage_woocommerce')) wp_die('You do not have permission to manage allocated tickets.');
        check_admin_referer('tn_allocated_ticket_save', 'tn_allocated_ticket_nonce');
        $post_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        $name = isset($_POST['preferred_name']) ? sanitize_text_field(wp_unslash($_POST['preferred_name'])) : '';
        $email = isset($_POST['email']) ? strtolower(sanitize_email(wp_unslash($_POST['email']))) : '';
        $amount = isset($_POST['amount_paid']) ? wc_format_decimal(wp_unslash($_POST['amount_paid']), 2) : '0.00';
        $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';
        $redirect = self::allocated_admin_url();
        if ($name === '' || !is_email($email)) {
            wp_safe_redirect(add_query_arg(['tn_alloc_notice' => 'invalid', 'ticket_id' => $post_id], $redirect));
            exit;
        }
        if ($post_id) {
            $post = get_post($post_id);
            if (!$post instanceof WP_Post || $post->post_type !== self::ALLOCATED_POST_TYPE) wp_die('Allocated ticket not found.');
            wp_update_post(['ID' => $post_id, 'post_title' => $name]);
        } else {
            $post_id = (int) wp_insert_post(['post_type' => self::ALLOCATED_POST_TYPE, 'post_status' => 'publish', 'post_title' => $name]);
            if (!$post_id) wp_die('The allocated ticket could not be created.');
            $sequence = max(1, absint(get_option(self::OPTION_ALLOCATED_SEQUENCE, 1)));
            update_post_meta($post_id, '_tn_alloc_code', 'TN26A-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT));
            update_option(self::OPTION_ALLOCATED_SEQUENCE, $sequence + 1);
        }
        update_post_meta($post_id, '_tn_alloc_email', $email);
        update_post_meta($post_id, '_tn_alloc_amount_paid', $amount);
        update_post_meta($post_id, '_tn_alloc_note', $note);
        wp_safe_redirect(add_query_arg(['tn_alloc_notice' => 'saved', 'ticket_id' => $post_id], $redirect));
        exit;
    }

    public static function render_allocated_tickets_page(): void {
        if (!current_user_can('manage_woocommerce')) return;
        $ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;
        $editing = $ticket_id ? get_post($ticket_id) : null;
        if (!$editing instanceof WP_Post || $editing->post_type !== self::ALLOCATED_POST_TYPE) $editing = null;
        $tickets = self::allocated_tickets();
        $notice = isset($_GET['tn_alloc_notice']) ? sanitize_key(wp_unslash($_GET['tn_alloc_notice'])) : '';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Allocated Tickets</h1>
            <a class="page-title-action" href="<?php echo esc_url(add_query_arg('add', '1', self::allocated_admin_url())); ?>">Add Ticket</a>
            <hr class="wp-header-end">
            <?php if ($notice === 'saved') : ?><div class="notice notice-success is-dismissible"><p>Allocated ticket saved.</p></div><?php endif; ?>
            <?php if ($notice === 'invalid') : ?><div class="notice notice-error"><p>Enter a preferred name and valid email address.</p></div><?php endif; ?>
            <?php if (isset($_GET['add']) || $editing) : ?>
                <div class="card" style="max-width:760px;margin-top:20px">
                    <h2><?php echo $editing ? 'Edit Allocated Ticket' : 'Add Allocated Ticket'; ?></h2>
                    <?php if ($editing) : ?><p><strong>Ticket number:</strong> <?php echo esc_html((string) get_post_meta($editing->ID, '_tn_alloc_code', true)); ?></p><?php endif; ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="tn_allocated_ticket_save">
                        <input type="hidden" name="ticket_id" value="<?php echo esc_attr($editing ? (string) $editing->ID : '0'); ?>">
                        <?php wp_nonce_field('tn_allocated_ticket_save', 'tn_allocated_ticket_nonce'); ?>
                        <table class="form-table" role="presentation">
                            <tr><th><label for="preferred_name">Preferred Name</label></th><td><input class="regular-text" id="preferred_name" name="preferred_name" required value="<?php echo esc_attr($editing ? $editing->post_title : ''); ?>"></td></tr>
                            <tr><th><label for="email">Email Address</label></th><td><input class="regular-text" id="email" name="email" type="email" required value="<?php echo esc_attr($editing ? (string) get_post_meta($editing->ID, '_tn_alloc_email', true) : ''); ?>"></td></tr>
                            <tr><th><label for="amount_paid">Amount Paid</label></th><td><input id="amount_paid" name="amount_paid" type="number" min="0" step="0.01" value="<?php echo esc_attr($editing ? (string) get_post_meta($editing->ID, '_tn_alloc_amount_paid', true) : '0.00'); ?>"></td></tr>
                            <tr><th><label for="note">Note</label></th><td><textarea class="large-text" id="note" name="note" rows="4"><?php echo esc_textarea($editing ? (string) get_post_meta($editing->ID, '_tn_alloc_note', true) : ''); ?></textarea><p class="description">For example: purchaser, payment method, sponsor, or allocation reason.</p></td></tr>
                        </table>
                        <?php submit_button($editing ? 'Update Ticket' : 'Add Ticket'); ?>
                    </form>
                </div>
            <?php endif; ?>
            <h2 style="margin-top:28px">Assigned Allocated Tickets</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Ticket</th><th>Preferred Name</th><th>Email</th><th>Amount Paid</th><th>Note</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $ticket) : ?>
                    <tr>
                        <td><strong><a href="<?php echo esc_url(add_query_arg('ticket_id', $ticket['id'], self::allocated_admin_url())); ?>"><?php echo esc_html($ticket['code']); ?></a></strong></td>
                        <td><?php echo esc_html($ticket['preferred_name']); ?><div class="row-actions"><span class="edit"><a href="<?php echo esc_url(add_query_arg('ticket_id', $ticket['id'], self::allocated_admin_url())); ?>">Edit</a></span></div></td>
                        <td><?php echo esc_html($ticket['email']); ?></td>
                        <td><?php echo wp_kses_post(wc_price((float) $ticket['amount_paid'])); ?></td>
                        <td><?php echo esc_html($ticket['note']); ?></td>
                        <td><?php echo $ticket['checkin'] ? '<span style="color:#008a20;font-weight:700">Checked in</span>' : 'Not checked in'; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tickets) : ?><tr><td colspan="6">No allocated tickets have been assigned.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function allocated_admin_url(): string {
        return admin_url('admin.php?page=tn-allocated-tickets');
    }

    public static function register_settings(): void {
        register_setting('tn_my_tickets', self::OPTION_PAGE_ID, ['type' => 'integer', 'sanitize_callback' => 'absint']);
        register_setting('tn_my_tickets', self::OPTION_SCANNER_PAGE_ID, ['type' => 'integer', 'sanitize_callback' => 'absint']);
        register_setting('tn_my_tickets', self::OPTION_PRODUCT_IDS, ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting('tn_my_tickets', self::OPTION_EVENT_DATES, ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
    }

    public static function render_settings_page(): void {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Trivia Nationals Electronic Tickets</h1>
            <p>WooCommerce remains the source of truth. Only paid, non-refunded orders for the selected products produce tickets.</p>
            <form method="post" action="options.php">
                <?php settings_fields('tn_my_tickets'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="tn_tickets_page_id">My Tickets page</label></th>
                        <td><?php wp_dropdown_pages(['name' => self::OPTION_PAGE_ID, 'id' => 'tn_tickets_page_id', 'selected' => absint(get_option(self::OPTION_PAGE_ID)), 'show_option_none' => '— Select a page —']); ?><p class="description">The page must contain <code>[tn_my_tickets]</code>. Activation creates it automatically when possible.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tn_tickets_scanner_page_id">Staff scanner page</label></th>
                        <td><?php wp_dropdown_pages(['name' => self::OPTION_SCANNER_PAGE_ID, 'id' => 'tn_tickets_scanner_page_id', 'selected' => absint(get_option(self::OPTION_SCANNER_PAGE_ID)), 'show_option_none' => '— Select a page —']); ?><p class="description">The page must contain <code>[tn_ticket_scanner]</code>.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tn_tickets_product_ids">Eligible product IDs</label></th>
                        <td><input class="regular-text" id="tn_tickets_product_ids" name="<?php echo esc_attr(self::OPTION_PRODUCT_IDS); ?>" value="<?php echo esc_attr((string) get_option(self::OPTION_PRODUCT_IDS)); ?>"><p class="description">Comma-separated WooCommerce product or variation IDs. The product name must also be exactly “<?php echo esc_html(self::ELIGIBLE_PRODUCT_NAME); ?>”.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tn_tickets_event_dates">Event dates</label></th>
                        <td><input class="regular-text" id="tn_tickets_event_dates" name="<?php echo esc_attr(self::OPTION_EVENT_DATES); ?>" value="<?php echo esc_attr((string) get_option(self::OPTION_EVENT_DATES, 'August 7–9, 2026')); ?>"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

register_activation_hook(__FILE__, [TN_My_Tickets::class, 'activate']);
add_action('plugins_loaded', static function (): void {
    if (class_exists('WooCommerce')) {
        TN_My_Tickets::activate();
        TN_My_Tickets::init();
    }
});

/**
 * @return array<int,array{id:string,name:string,preferred_name:string,email:string}>
 */
function tn_tickets_attendee_roster(): array {
    return TN_My_Tickets::attendee_roster();
}
