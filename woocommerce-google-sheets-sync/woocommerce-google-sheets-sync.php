<?php
/**
 * Plugin Name: Trivia Nationals WooCommerce Google Sheets Sync
 * Description: Upserts WooCommerce orders into the Trivia Nationals Preferred Names Google Sheet.
 * Version: 1.0.5
 * Author: Trivia Nationals
 */

if (!defined('ABSPATH')) {
    exit;
}

final class TN_WC_Google_Sheets_Sync {
    private const OPTION_ENDPOINT = 'tn_wc_sheets_endpoint';
    private const OPTION_SECRET = 'tn_wc_sheets_secret';
    private const ASYNC_HOOK = 'tn_wc_sheets_sync_order';
    private const BACKFILL_HOOK = 'tn_wc_sheets_backfill_batch';

    public static function init(): void {
        add_action('woocommerce_new_order', [self::class, 'queue_order'], 20, 1);
        add_action('woocommerce_order_status_changed', [self::class, 'queue_order'], 20, 1);
        add_action('woocommerce_update_order', [self::class, 'queue_order'], 20, 1);
        add_action(self::ASYNC_HOOK, [self::class, 'sync_order'], 10, 1);
        add_action(self::BACKFILL_HOOK, [self::class, 'process_backfill_batch'], 10, 2);

        add_action('admin_menu', [self::class, 'add_settings_page']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_post_tn_wc_sheets_test', [self::class, 'handle_test']);
        add_action('admin_post_tn_wc_sheets_backfill', [self::class, 'handle_backfill']);
    }

    public static function queue_order($order_id): void {
        $order_id = absint($order_id);
        if (!$order_id) {
            return;
        }

        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::ASYNC_HOOK, [$order_id], 'tn-wc-sheets', true);
            return;
        }

        if (!wp_next_scheduled(self::ASYNC_HOOK, [$order_id])) {
            wp_schedule_single_event(time() + 5, self::ASYNC_HOOK, [$order_id]);
        }
    }

    public static function sync_order($order_id): bool {
        $endpoint = trim((string) get_option(self::OPTION_ENDPOINT));
        $secret = trim((string) get_option(self::OPTION_SECRET));
        $order = wc_get_order(absint($order_id));

        if (!$endpoint || !$secret || !$order) {
            return false;
        }

        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'redirection' => 0,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => wp_json_encode(self::build_payload($order, $secret)),
        ]);

        if (is_wp_error($response)) {
            error_log('TN WooCommerce Sheets sync failed: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 300 && $code < 400) {
            $location = wp_remote_retrieve_header($response, 'location');
            if (!$location) {
                error_log('TN WooCommerce Sheets sync redirect did not include a Location header.');
                return false;
            }

            $response = wp_remote_get($location, [
                'timeout' => 20,
                'redirection' => 5,
            ]);
            if (is_wp_error($response)) {
                error_log('TN WooCommerce Sheets sync response fetch failed: ' . $response->get_error_message());
                return false;
            }
            $code = wp_remote_retrieve_response_code($response);
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        if ($code < 200 || $code >= 300 || !is_array($result) || empty($result['ok'])) {
            error_log('TN WooCommerce Sheets sync returned HTTP ' . $code . ': ' . $body);
            return false;
        }

        return true;
    }

    private static function build_payload(WC_Order $order, string $secret): array {
        $items = [];
        $item_options = [];

        foreach ($order->get_items('line_item') as $item) {
            $items[] = sprintf('%s x%s', $item->get_name(), $item->get_quantity());

            foreach ($item->get_meta_data() as $meta) {
                $key = (string) $meta->key;
                if ($key === '' || str_starts_with($key, '_')) {
                    continue;
                }

                $label = wc_attribute_label($key, $item->get_product());
                $value = is_scalar($meta->value)
                    ? (string) $meta->value
                    : wp_json_encode($meta->value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $item_options[] = sprintf('%s: %s', $label ?: $key, wp_strip_all_tags($value));
            }
        }

        return [
            'secret' => $secret,
            'action' => 'order_upsert',
            'order' => [
                'order_id' => (string) $order->get_id(),
                'order_date' => $order->get_date_created()
                    ? $order->get_date_created()->date('Y-m-d H:i:s')
                    : '',
                'status' => 'wc-' . $order->get_status(),
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'total' => (string) $order->get_total(),
                'currency' => $order->get_currency(),
                'payment_method' => $order->get_payment_method_title(),
                'items' => implode(' | ', $items),
                'item_options' => implode(' | ', $item_options),
                'coupon_codes' => implode(' | ', $order->get_coupon_codes()),
            ],
        ];
    }

    public static function add_settings_page(): void {
        add_submenu_page(
            'woocommerce',
            'Google Sheets Sync',
            'Google Sheets Sync',
            'manage_woocommerce',
            'tn-wc-sheets-sync',
            [self::class, 'render_settings_page']
        );
    }

    public static function register_settings(): void {
        register_setting('tn_wc_sheets_sync', self::OPTION_ENDPOINT, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ]);
        register_setting('tn_wc_sheets_sync', self::OPTION_SECRET, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
    }

    public static function render_settings_page(): void {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $notice = isset($_GET['tn_sync']) ? sanitize_key(wp_unslash($_GET['tn_sync'])) : '';
        $backfill_started = isset($_GET['tn_backfill_started']) ? sanitize_text_field(wp_unslash($_GET['tn_backfill_started'])) : '';
        ?>
        <div class="wrap">
            <h1>Trivia Nationals Google Sheets Sync</h1>
            <?php if ($notice === 'success') : ?>
                <div class="notice notice-success"><p>The latest order was sent successfully.</p></div>
            <?php elseif ($notice === 'failed') : ?>
                <div class="notice notice-error"><p>The test sync failed. Check the endpoint, secret, and PHP error log.</p></div>
            <?php elseif ($notice === 'backfill') : ?>
                <div class="notice notice-success"><p>
                    Date backfill started for orders from <?php echo esc_html($backfill_started); ?> onward.
                </p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('tn_wc_sheets_sync'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="tn_wc_sheets_endpoint">Apps Script web app URL</label></th>
                        <td><input class="regular-text code" type="url" id="tn_wc_sheets_endpoint" name="<?php echo esc_attr(self::OPTION_ENDPOINT); ?>" value="<?php echo esc_attr(get_option(self::OPTION_ENDPOINT)); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tn_wc_sheets_secret">Shared secret</label></th>
                        <td><input class="regular-text code" type="password" id="tn_wc_sheets_secret" name="<?php echo esc_attr(self::OPTION_SECRET); ?>" value="<?php echo esc_attr(get_option(self::OPTION_SECRET)); ?>" autocomplete="new-password"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <hr>
            <h2>Test</h2>
            <p>This resends the latest existing WooCommerce order. The sheet row is updated by order ID, not duplicated.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="tn_wc_sheets_test">
                <?php wp_nonce_field('tn_wc_sheets_test'); ?>
                <?php submit_button('Send latest order', 'secondary', 'submit', false); ?>
            </form>
            <hr>
            <h2>Backfill</h2>
            <p>Send existing WooCommerce orders created on or after the start date. Existing sheet rows are updated by order ID, not duplicated.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="tn_wc_sheets_backfill">
                <?php wp_nonce_field('tn_wc_sheets_backfill'); ?>
                <label for="tn_wc_sheets_start_date">Sync orders from</label>
                <input type="date" id="tn_wc_sheets_start_date" name="start_date" value="2025-12-09" required>
                <?php submit_button('Start date backfill', 'secondary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    public static function handle_test(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }
        check_admin_referer('tn_wc_sheets_test');

        $orders = wc_get_orders([
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
        ]);
        $success = $orders && self::sync_order($orders[0]);

        wp_safe_redirect(add_query_arg(
            'tn_sync',
            $success ? 'success' : 'failed',
            admin_url('admin.php?page=tn-wc-sheets-sync')
        ));
        exit;
    }

    public static function handle_backfill(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }
        check_admin_referer('tn_wc_sheets_backfill');

        $start_date = isset($_POST['start_date'])
            ? sanitize_text_field(wp_unslash($_POST['start_date']))
            : '';
        $parsed_date = DateTimeImmutable::createFromFormat('!Y-m-d', $start_date);
        if (!$parsed_date || $parsed_date->format('Y-m-d') !== $start_date) {
            wp_die('A valid start date is required.');
        }

        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::BACKFILL_HOOK, [$start_date, 1], 'tn-wc-sheets');
        } else {
            wp_schedule_single_event(time() + 1, self::BACKFILL_HOOK, [$start_date, 1]);
        }

        wp_safe_redirect(add_query_arg([
            'tn_sync' => 'backfill',
            'tn_backfill_started' => $start_date,
        ], admin_url('admin.php?page=tn-wc-sheets-sync')));
        exit;
    }

    public static function process_backfill_batch($start_date, $page): void {
        $start_date = sanitize_text_field((string) $start_date);
        $page = max(1, absint($page));
        $timestamp = strtotime($start_date . ' 00:00:00');
        if (!$timestamp) {
            return;
        }

        global $wpdb;
        $batch_size = 3;
        $offset = ($page - 1) * $batch_size;
        $order_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT ID
             FROM {$wpdb->posts}
             WHERE post_type = 'shop_order'
               AND post_status <> 'trash'
               AND post_date >= %s
             ORDER BY post_date ASC, ID ASC
             LIMIT %d OFFSET %d",
            $start_date . ' 00:00:00',
            $batch_size,
            $offset
        ));

        foreach ($order_ids as $order_id) {
            self::sync_order($order_id);
        }

        if (count($order_ids) === $batch_size) {
            if (function_exists('as_enqueue_async_action')) {
                as_enqueue_async_action(self::BACKFILL_HOOK, [$start_date, $page + 1], 'tn-wc-sheets');
            } else {
                wp_schedule_single_event(time() + 5, self::BACKFILL_HOOK, [$start_date, $page + 1]);
            }
        }
    }
}

add_action('plugins_loaded', static function (): void {
    if (class_exists('WooCommerce')) {
        TN_WC_Google_Sheets_Sync::init();
    }
});
