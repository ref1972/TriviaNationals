<?php
/**
 * Plugin Name: Trivia Nationals Event Schedule
 * Description: Adds a Trivia Nationals event schedule with rich descriptions that support embedded graphics.
 * Version: 1.0.0
 * Author: Trivia Nationals
 */

if (!defined('ABSPATH')) {
    exit;
}

final class TN_Event_Schedule {
    private const POST_TYPE = 'tn_event';
    private const SIGNUP_POST_TYPE = 'tn_event_signup';
    private const META_START = '_tn_event_start';
    private const META_END = '_tn_event_end';
    private const META_LOCATION = '_tn_event_location';
    private const META_URL = '_tn_event_url';
    private const META_SIGNUP_ENABLED = '_tn_event_signup_enabled';
    private const META_SIGNUP_NOTE = '_tn_event_signup_note';
    private const META_SIGNUP_FLIGHTS = '_tn_event_signup_flights';
    private const SIGNUP_META_EVENT_ID = '_tn_signup_event_id';
    private const SIGNUP_META_EVENT_TITLE = '_tn_signup_event_title';
    private const SIGNUP_META_NAME = '_tn_signup_name';
    private const SIGNUP_META_EMAIL = '_tn_signup_email';
    private const SIGNUP_META_FLIGHT = '_tn_signup_flight';
    private const SIGNUP_META_TEAM = '_tn_signup_team';
    private const SIGNUP_META_TEAM_MEMBERS = '_tn_signup_team_members';
    private const SIGNUP_META_NOTES = '_tn_signup_notes';
    private const SIGNUP_META_SYNC_STATUS = '_tn_signup_sync_status';
    private const SIGNUP_META_SYNC_ERROR = '_tn_signup_sync_error';
    private const OPTION_SHEETS_ENDPOINT = 'tn_event_signup_sheets_endpoint';
    private const OPTION_SHEETS_SECRET = 'tn_event_signup_sheets_secret';
    private const ASYNC_SIGNUP_HOOK = 'tn_event_signup_sync';
    private const NONCE_ACTION = 'tn_event_schedule_save';
    private const NONCE_NAME = 'tn_event_schedule_nonce';
    private const SIGNUP_NONCE_ACTION = 'tn_event_signup_submit';
    private const SIGNUP_NONCE_NAME = 'tn_event_signup_nonce';
    private const DEFAULT_SIGNUP_EVENT_TITLES = [
        'Quiz Bowl',
        'BP Titans',
        '5x5',
        'Trivia The Gathering',
        'Trivia the Gathering',
        'TTG',
        'Trivia Spelling Bee',
        'Academic Bee',
        'Pop Culture Bee',
        'Crossword Challenge',
        'IQA Individual Championship',
    ];
    private const TEAM_SIGNUP_EVENT_TITLES = [
        'Quiz Bowl',
        'BP Titans',
    ];

    public static function init(): void {
        add_action('init', [self::class, 'register_post_type']);
        add_action('init', [self::class, 'register_signup_post_type']);
        add_action('add_meta_boxes', [self::class, 'add_event_details_box']);
        add_action('save_post_' . self::POST_TYPE, [self::class, 'save_event_details']);
        add_action('admin_head-post.php', [self::class, 'add_editor_note']);
        add_action('admin_head-post-new.php', [self::class, 'add_editor_note']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_menu', [self::class, 'add_settings_page']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_post_tn_event_signup', [self::class, 'handle_signup']);
        add_action('admin_post_nopriv_tn_event_signup', [self::class, 'handle_signup']);
        add_action(self::ASYNC_SIGNUP_HOOK, [self::class, 'sync_signup'], 10, 1);
        add_shortcode('trivia_nationals_event_schedule', [self::class, 'render_schedule_shortcode']);
        add_shortcode('trivia_nationals_event_signup', [self::class, 'render_signup_shortcode']);
        add_filter('the_content', [self::class, 'append_signup_form_to_event']);
        add_filter('the_content', [self::class, 'linkify_event_description'], 20);
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Events',
                'singular_name' => 'Event',
                'add_new_item' => 'Add New Event',
                'edit_item' => 'Edit Event',
                'new_item' => 'New Event',
                'view_item' => 'View Event',
                'search_items' => 'Search Events',
                'not_found' => 'No events found',
            ],
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'events'],
        ]);
    }

    public static function register_signup_post_type(): void {
        register_post_type(self::SIGNUP_POST_TYPE, [
            'labels' => [
                'name' => 'Event Signups',
                'singular_name' => 'Event Signup',
                'menu_name' => 'Signups',
                'edit_item' => 'View Signup',
                'search_items' => 'Search Signups',
                'not_found' => 'No signups found',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=' . self::POST_TYPE,
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap' => true,
            'supports' => ['title', 'custom-fields'],
        ]);
    }

    public static function add_event_details_box(): void {
        add_meta_box(
            'tn_event_details',
            'Event Details',
            [self::class, 'render_event_details_box'],
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    public static function render_event_details_box(WP_Post $post): void {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $start = self::datetime_value((string) get_post_meta($post->ID, self::META_START, true));
        $end = self::datetime_value((string) get_post_meta($post->ID, self::META_END, true));
        $location = (string) get_post_meta($post->ID, self::META_LOCATION, true);
        $url = (string) get_post_meta($post->ID, self::META_URL, true);
        $signup_enabled = self::event_accepts_signups($post->ID);
        $signup_note = (string) get_post_meta($post->ID, self::META_SIGNUP_NOTE, true);
        $signup_flights = (string) get_post_meta($post->ID, self::META_SIGNUP_FLIGHTS, true);
        ?>
        <p>
            <label for="tn_event_start"><strong>Start</strong></label>
            <input type="datetime-local" id="tn_event_start" name="tn_event_start" value="<?php echo esc_attr($start); ?>" class="widefat">
        </p>
        <p>
            <label for="tn_event_end"><strong>End</strong></label>
            <input type="datetime-local" id="tn_event_end" name="tn_event_end" value="<?php echo esc_attr($end); ?>" class="widefat">
        </p>
        <p>
            <label for="tn_event_location"><strong>Location</strong></label>
            <input type="text" id="tn_event_location" name="tn_event_location" value="<?php echo esc_attr($location); ?>" class="widefat">
        </p>
        <p>
            <label for="tn_event_url"><strong>Registration or info URL</strong></label>
            <input type="url" id="tn_event_url" name="tn_event_url" value="<?php echo esc_url($url); ?>" class="widefat">
        </p>
        <hr>
        <p>
            <label>
                <input type="checkbox" name="tn_event_signup_enabled" value="1" <?php checked($signup_enabled); ?>>
                <strong>Enable signup form</strong>
            </label>
        </p>
        <p class="description">Default signup events are enabled automatically, but saving this checkbox overrides the default for this event.</p>
        <p>
            <label for="tn_event_signup_note"><strong>Signup note</strong></label>
            <textarea id="tn_event_signup_note" name="tn_event_signup_note" class="widefat" rows="3" placeholder="Optional note shown above the form."><?php echo esc_textarea($signup_note); ?></textarea>
        </p>
        <p>
            <label for="tn_event_signup_flights"><strong>Signup flights</strong></label>
            <textarea id="tn_event_signup_flights" name="tn_event_signup_flights" class="widefat" rows="3" placeholder="Optional. One flight per line, e.g. Flight A&#10;Flight B"><?php echo esc_textarea($signup_flights); ?></textarea>
        </p>
        <p class="description">Leave blank to infer flights from session data or event text when available.</p>
        <?php
    }

    public static function save_event_details(int $post_id): void {
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        self::save_meta($post_id, self::META_START, 'tn_event_start', [self::class, 'sanitize_datetime']);
        self::save_meta($post_id, self::META_END, 'tn_event_end', [self::class, 'sanitize_datetime']);
        self::save_meta($post_id, self::META_LOCATION, 'tn_event_location', 'sanitize_text_field');
        self::save_meta($post_id, self::META_URL, 'tn_event_url', 'esc_url_raw');
        update_post_meta($post_id, self::META_SIGNUP_ENABLED, isset($_POST['tn_event_signup_enabled']) ? '1' : '0');
        self::save_meta($post_id, self::META_SIGNUP_NOTE, 'tn_event_signup_note', 'sanitize_textarea_field');
        self::save_meta($post_id, self::META_SIGNUP_FLIGHTS, 'tn_event_signup_flights', [self::class, 'sanitize_flight_options']);
    }

    public static function add_settings_page(): void {
        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            'Event Signup Settings',
            'Signup Settings',
            'manage_options',
            'tn-event-signup-settings',
            [self::class, 'render_settings_page']
        );
    }

    public static function register_settings(): void {
        register_setting('tn_event_signup_settings', self::OPTION_SHEETS_ENDPOINT, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ]);
        register_setting('tn_event_signup_settings', self::OPTION_SHEETS_SECRET, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
    }

    public static function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Event Signup Settings</h1>
            <p>Paste the Google Apps Script web app URL and shared secret used to append event signup rows to Google Sheets.</p>
            <form method="post" action="options.php">
                <?php settings_fields('tn_event_signup_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="tn_event_signup_sheets_endpoint">Apps Script web app URL</label></th>
                        <td><input class="regular-text code" type="url" id="tn_event_signup_sheets_endpoint" name="<?php echo esc_attr(self::OPTION_SHEETS_ENDPOINT); ?>" value="<?php echo esc_attr(get_option(self::OPTION_SHEETS_ENDPOINT)); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tn_event_signup_sheets_secret">Shared secret</label></th>
                        <td><input class="regular-text code" type="password" id="tn_event_signup_sheets_secret" name="<?php echo esc_attr(self::OPTION_SHEETS_SECRET); ?>" value="<?php echo esc_attr(get_option(self::OPTION_SHEETS_SECRET)); ?>" autocomplete="new-password"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function add_editor_note(): void {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== self::POST_TYPE) {
            return;
        }
        ?>
        <style>
            .tn-event-editor-note {
                margin: 10px 0 0;
                color: #50575e;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var editor = document.querySelector('#postdivrich, .editor-styles-wrapper');
                if (!editor || document.querySelector('.tn-event-editor-note')) return;
                var note = document.createElement('p');
                note.className = 'tn-event-editor-note';
                note.textContent = 'Use the editor Add Media button or image block to place graphics directly inside the event description.';
                editor.parentNode.insertBefore(note, editor);
            });
        </script>
        <?php
    }

    public static function enqueue_assets(): void {
        wp_register_style('tn-event-schedule', false, [], '1.0.0');
        wp_enqueue_style('tn-event-schedule');
        wp_add_inline_style('tn-event-schedule', self::schedule_css());
    }

    public static function render_schedule_shortcode(array $atts): string {
        $atts = shortcode_atts([
            'limit' => 20,
            'show_past' => 'false',
        ], $atts, 'trivia_nationals_event_schedule');

        $now = current_time('mysql');
        $meta_query = [];
        if ($atts['show_past'] !== 'true') {
            $meta_query[] = [
                'key' => self::META_START,
                'value' => $now,
                'compare' => '>=',
                'type' => 'DATETIME',
            ];
        }

        $events = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => max(1, absint($atts['limit'])),
            'meta_key' => self::META_START,
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => $meta_query,
        ]);

        if (!$events->have_posts()) {
            return '<p class="tn-event-schedule-empty">No events are currently scheduled.</p>';
        }

        ob_start();
        ?>
        <div class="tn-event-schedule">
            <?php while ($events->have_posts()) : $events->the_post(); ?>
                <?php self::render_event_card(get_post()); ?>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    private static function render_event_card(WP_Post $post): void {
        $start = (string) get_post_meta($post->ID, self::META_START, true);
        $end = (string) get_post_meta($post->ID, self::META_END, true);
        $location = (string) get_post_meta($post->ID, self::META_LOCATION, true);
        $url = (string) get_post_meta($post->ID, self::META_URL, true);
        ?>
        <article class="tn-event-card">
            <?php if (has_post_thumbnail($post)) : ?>
                <a class="tn-event-card__image" href="<?php echo esc_url(get_permalink($post)); ?>">
                    <?php echo get_the_post_thumbnail($post, 'large'); ?>
                </a>
            <?php endif; ?>
            <div class="tn-event-card__body">
                <h3 class="tn-event-card__title">
                    <a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a>
                </h3>
                <div class="tn-event-card__meta">
                    <?php if ($start) : ?>
                        <span><?php echo esc_html(self::format_datetime($start)); ?></span>
                    <?php endif; ?>
                    <?php if ($end) : ?>
                        <span>Ends <?php echo esc_html(self::format_datetime($end)); ?></span>
                    <?php endif; ?>
                    <?php if ($location) : ?>
                        <span><?php echo esc_html($location); ?></span>
                    <?php endif; ?>
                </div>
                <div class="tn-event-card__description">
                    <?php echo apply_filters('the_content', get_the_content(null, false, $post)); ?>
                </div>
                <?php if ($url) : ?>
                    <p class="tn-event-card__action">
                        <a href="<?php echo esc_url($url); ?>">Event details</a>
                    </p>
                <?php endif; ?>
                <?php if (self::event_accepts_signups($post->ID)) : ?>
                    <?php echo self::render_signup_form($post); ?>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }

    public static function render_signup_shortcode(array $atts): string {
        $atts = shortcode_atts([
            'event_id' => 0,
        ], $atts, 'trivia_nationals_event_signup');

        $event_id = absint($atts['event_id']) ?: get_the_ID();
        if (!$event_id) {
            return '';
        }

        $post = get_post($event_id);
        if (!$post || $post->post_type !== self::POST_TYPE || !self::event_accepts_signups($event_id)) {
            return '';
        }

        return self::render_signup_form($post);
    }

    public static function append_signup_form_to_event(string $content): string {
        if (!is_singular(self::POST_TYPE) || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post = get_post();
        if (!$post || !self::event_accepts_signups($post->ID)) {
            return $content;
        }

        return $content . self::render_signup_form($post);
    }

    public static function linkify_event_description(string $content): string {
        if (get_post_type() !== self::POST_TYPE) {
            return $content;
        }

        return make_clickable($content);
    }

    public static function handle_signup(): void {
        $event_id = isset($_POST['tn_event_id']) ? absint($_POST['tn_event_id']) : 0;
        $posted_redirect = isset($_POST['tn_signup_redirect']) ? esc_url_raw(wp_unslash($_POST['tn_signup_redirect'])) : '';
        $redirect = $posted_redirect ?: ($event_id ? get_permalink($event_id) : home_url('/'));

        if (!isset($_POST[self::SIGNUP_NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::SIGNUP_NONCE_NAME])), self::SIGNUP_NONCE_ACTION)) {
            wp_safe_redirect(add_query_arg('tn_signup', 'invalid', $redirect));
            exit;
        }

        if (!empty($_POST['tn_event_signup_company'])) {
            wp_safe_redirect(add_query_arg('tn_signup', 'success', $redirect));
            exit;
        }

        $event = get_post($event_id);
        if (!$event || $event->post_type !== self::POST_TYPE || !self::event_accepts_signups($event_id)) {
            wp_safe_redirect(add_query_arg('tn_signup', 'closed', $redirect));
            exit;
        }

        $name = isset($_POST['tn_signup_name']) ? sanitize_text_field(wp_unslash($_POST['tn_signup_name'])) : '';
        $email = isset($_POST['tn_signup_email']) ? sanitize_email(wp_unslash($_POST['tn_signup_email'])) : '';
        $flight = isset($_POST['tn_signup_flight']) ? sanitize_text_field(wp_unslash($_POST['tn_signup_flight'])) : '';
        $team = isset($_POST['tn_signup_team']) ? sanitize_text_field(wp_unslash($_POST['tn_signup_team'])) : '';
        $team_members = isset($_POST['tn_signup_team_members']) ? sanitize_textarea_field(wp_unslash($_POST['tn_signup_team_members'])) : '';
        $notes = isset($_POST['tn_signup_notes']) ? sanitize_textarea_field(wp_unslash($_POST['tn_signup_notes'])) : '';
        $flight_options = self::get_event_signup_flights($event_id);

        if ($name === '' || !is_email($email) || ($flight_options && !in_array($flight, $flight_options, true))) {
            wp_safe_redirect(add_query_arg('tn_signup', 'missing', $redirect));
            exit;
        }

        $signup_id = wp_insert_post([
            'post_type' => self::SIGNUP_POST_TYPE,
            'post_status' => 'private',
            'post_title' => sprintf('Signup: %s - %s', get_the_title($event), $name),
        ], true);

        if (is_wp_error($signup_id)) {
            wp_safe_redirect(add_query_arg('tn_signup', 'error', $redirect));
            exit;
        }

        update_post_meta($signup_id, self::SIGNUP_META_EVENT_ID, (string) $event_id);
        update_post_meta($signup_id, self::SIGNUP_META_EVENT_TITLE, get_the_title($event));
        update_post_meta($signup_id, self::SIGNUP_META_NAME, $name);
        update_post_meta($signup_id, self::SIGNUP_META_EMAIL, $email);
        update_post_meta($signup_id, self::SIGNUP_META_FLIGHT, $flight);
        update_post_meta($signup_id, self::SIGNUP_META_TEAM, $team);
        update_post_meta($signup_id, self::SIGNUP_META_TEAM_MEMBERS, $team_members);
        update_post_meta($signup_id, self::SIGNUP_META_NOTES, $notes);
        update_post_meta($signup_id, self::SIGNUP_META_SYNC_STATUS, 'pending');

        self::queue_signup_sync((int) $signup_id);

        wp_safe_redirect(add_query_arg('tn_signup', 'success', $redirect));
        exit;
    }

    public static function queue_signup_sync(int $signup_id): void {
        if (!$signup_id) {
            return;
        }

        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(self::ASYNC_SIGNUP_HOOK, [$signup_id], 'tn-event-signups', true);
            return;
        }

        if (!wp_next_scheduled(self::ASYNC_SIGNUP_HOOK, [$signup_id])) {
            wp_schedule_single_event(time() + 5, self::ASYNC_SIGNUP_HOOK, [$signup_id]);
        }
    }

    public static function sync_signup(int $signup_id): bool {
        $endpoint = trim((string) get_option(self::OPTION_SHEETS_ENDPOINT));
        $secret = trim((string) get_option(self::OPTION_SHEETS_SECRET));
        $signup = get_post(absint($signup_id));

        if (!$endpoint || !$secret || !$signup || $signup->post_type !== self::SIGNUP_POST_TYPE) {
            return false;
        }

        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'redirection' => 0,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => wp_json_encode(self::build_signup_payload($signup, $secret)),
        ]);

        if (is_wp_error($response)) {
            self::mark_signup_sync_failed($signup_id, $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 300 && $code < 400) {
            $location = wp_remote_retrieve_header($response, 'location');
            if (!$location) {
                self::mark_signup_sync_failed($signup_id, 'Redirect did not include a Location header.');
                return false;
            }

            $response = wp_remote_get($location, [
                'timeout' => 20,
                'redirection' => 5,
            ]);
            if (is_wp_error($response)) {
                self::mark_signup_sync_failed($signup_id, $response->get_error_message());
                return false;
            }
            $code = wp_remote_retrieve_response_code($response);
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        if ($code < 200 || $code >= 300 || !is_array($result) || empty($result['ok'])) {
            self::mark_signup_sync_failed($signup_id, 'HTTP ' . $code . ': ' . $body);
            return false;
        }

        update_post_meta($signup_id, self::SIGNUP_META_SYNC_STATUS, 'synced');
        delete_post_meta($signup_id, self::SIGNUP_META_SYNC_ERROR);
        return true;
    }

    private static function render_signup_form(WP_Post $post): string {
        $status = isset($_GET['tn_signup']) ? sanitize_key(wp_unslash($_GET['tn_signup'])) : '';
        $note = (string) get_post_meta($post->ID, self::META_SIGNUP_NOTE, true);
        $redirect = remove_query_arg('tn_signup');
        $is_team_signup = self::event_uses_team_members($post->ID);
        $flight_options = self::get_event_signup_flights($post->ID);

        ob_start();
        ?>
        <section class="tn-event-signup" aria-label="<?php echo esc_attr(get_the_title($post)); ?> signup">
            <h4 class="tn-event-signup__title">Sign up for this event</h4>
            <?php if ($note) : ?>
                <p class="tn-event-signup__note"><?php echo esc_html($note); ?></p>
            <?php endif; ?>
            <?php if ($status === 'success') : ?>
                <p class="tn-event-signup__message tn-event-signup__message--success">Thanks! Your signup was received.</p>
            <?php elseif (in_array($status, ['invalid', 'closed', 'missing', 'error'], true)) : ?>
                <p class="tn-event-signup__message tn-event-signup__message--error">Sorry, that signup could not be saved. Please check the required fields and try again.</p>
            <?php endif; ?>
            <form class="tn-event-signup__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="tn_event_signup">
                <input type="hidden" name="tn_event_id" value="<?php echo esc_attr((string) $post->ID); ?>">
                <input type="hidden" name="tn_signup_redirect" value="<?php echo esc_url($redirect); ?>">
                <?php wp_nonce_field(self::SIGNUP_NONCE_ACTION, self::SIGNUP_NONCE_NAME); ?>
                <p class="tn-event-signup__field">
                    <label for="tn_signup_name_<?php echo esc_attr((string) $post->ID); ?>">Name *</label>
                    <input type="text" id="tn_signup_name_<?php echo esc_attr((string) $post->ID); ?>" name="tn_signup_name" required autocomplete="name">
                </p>
                <p class="tn-event-signup__field">
                    <label for="tn_signup_email_<?php echo esc_attr((string) $post->ID); ?>">Email *</label>
                    <input type="email" id="tn_signup_email_<?php echo esc_attr((string) $post->ID); ?>" name="tn_signup_email" required autocomplete="email">
                </p>
                <?php if ($flight_options) : ?>
                    <p class="tn-event-signup__field">
                        <label for="tn_signup_flight_<?php echo esc_attr((string) $post->ID); ?>">Flight *</label>
                        <select id="tn_signup_flight_<?php echo esc_attr((string) $post->ID); ?>" name="tn_signup_flight" required>
                            <option value="">Select a flight</option>
                            <?php foreach ($flight_options as $flight_option) : ?>
                                <option value="<?php echo esc_attr($flight_option); ?>"><?php echo esc_html($flight_option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                <?php endif; ?>
                <p class="tn-event-signup__field">
                    <label for="tn_signup_team_<?php echo esc_attr((string) $post->ID); ?>">Team / group</label>
                    <input type="text" id="tn_signup_team_<?php echo esc_attr((string) $post->ID); ?>" name="tn_signup_team">
                </p>
                <?php if ($is_team_signup) : ?>
                    <p class="tn-event-signup__field tn-event-signup__field--full">
                        <label for="tn_signup_team_members_<?php echo esc_attr((string) $post->ID); ?>">Team members</label>
                        <textarea id="tn_signup_team_members_<?php echo esc_attr((string) $post->ID); ?>" name="tn_signup_team_members" rows="3" placeholder="One person can register the whole team. List teammates here if you have them."></textarea>
                    </p>
                <?php endif; ?>
                <p class="tn-event-signup__field tn-event-signup__field--full">
                    <label for="tn_signup_notes_<?php echo esc_attr((string) $post->ID); ?>">Notes</label>
                    <textarea id="tn_signup_notes_<?php echo esc_attr((string) $post->ID); ?>" name="tn_signup_notes" rows="3"></textarea>
                </p>
                <p class="tn-event-signup__trap">
                    <label for="tn_event_signup_company_<?php echo esc_attr((string) $post->ID); ?>">Company</label>
                    <input type="text" id="tn_event_signup_company_<?php echo esc_attr((string) $post->ID); ?>" name="tn_event_signup_company" tabindex="-1" autocomplete="off">
                </p>
                <p class="tn-event-signup__submit">
                    <button type="submit">Submit signup</button>
                </p>
            </form>
        </section>
        <?php

        return (string) ob_get_clean();
    }

    private static function event_accepts_signups(int $event_id): bool {
        $saved = get_post_meta($event_id, self::META_SIGNUP_ENABLED, true);
        if ($saved === '1') {
            return true;
        }
        if ($saved === '0') {
            return false;
        }

        return self::title_is_default_signup_event(get_the_title($event_id));
    }

    private static function title_is_default_signup_event(string $title): bool {
        $normalized_title = self::normalize_event_title($title);
        foreach (self::DEFAULT_SIGNUP_EVENT_TITLES as $signup_title) {
            $normalized_signup_title = self::normalize_event_title($signup_title);
            if ($normalized_title === $normalized_signup_title || strpos($normalized_title, $normalized_signup_title) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function event_uses_team_members(int $event_id): bool {
        $normalized_title = self::normalize_event_title(get_the_title($event_id));
        foreach (self::TEAM_SIGNUP_EVENT_TITLES as $team_title) {
            $normalized_team_title = self::normalize_event_title($team_title);
            if ($normalized_title === $normalized_team_title || strpos($normalized_title, $normalized_team_title) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function get_event_signup_flights(int $event_id): array {
        $raw = (string) get_post_meta($event_id, self::META_SIGNUP_FLIGHTS, true);
        if ($raw !== '') {
            $manual_flights = preg_split('/\r\n|\r|\n/', $raw);
            return self::normalize_flight_options(is_array($manual_flights) ? $manual_flights : []);
        }

        return self::infer_event_signup_flights($event_id);
    }

    private static function infer_event_signup_flights(int $event_id): array {
        $flights = [];
        $session_meta_keys = [
            '_tn_event_sessions',
            'tn_event_sessions',
            'sessions',
            'data-sessions',
        ];

        foreach ($session_meta_keys as $meta_key) {
            $value = get_post_meta($event_id, $meta_key, true);
            if ($value === '') {
                continue;
            }

            $flights = array_merge($flights, self::extract_flights_from_sessions($value));
        }

        $post = get_post($event_id);
        if ($post) {
            $text = html_entity_decode(wp_strip_all_tags($post->post_title . "\n" . $post->post_excerpt . "\n" . $post->post_content), ENT_QUOTES, get_bloginfo('charset'));
            $flights = array_merge($flights, self::extract_flights_from_text($text));

            if (preg_match_all('/data-sessions=(["\'])(.*?)\1/i', $post->post_content, $matches)) {
                foreach ($matches[2] as $sessions_json) {
                    $flights = array_merge($flights, self::extract_flights_from_sessions(html_entity_decode($sessions_json, ENT_QUOTES, 'UTF-8')));
                }
            }
        }

        return self::normalize_flight_options($flights);
    }

    private static function extract_flights_from_sessions($sessions): array {
        if (is_string($sessions)) {
            $decoded = json_decode(html_entity_decode($sessions, ENT_QUOTES, 'UTF-8'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $sessions = $decoded;
            }
        }

        if (!is_array($sessions)) {
            return [];
        }

        $flights = [];
        foreach ($sessions as $session) {
            if (!is_array($session)) {
                continue;
            }

            foreach (['label', 'title', 'name'] as $key) {
                if (!empty($session[$key]) && is_scalar($session[$key])) {
                    $flights[] = (string) $session[$key];
                }
            }
        }

        return $flights;
    }

    private static function extract_flights_from_text(string $text): array {
        if ($text === '') {
            return [];
        }

        $flights = [];
        if (preg_match_all('/\b(?:Prelim(?:inary)?\s+)?Flight\s+[A-Z0-9](?:\s*(?:&|and|\/)\s*[A-Z0-9])?\b/i', $text, $matches)) {
            $flights = array_merge($flights, $matches[0]);
        }

        return $flights;
    }

    private static function normalize_flight_options(array $flights): array {
        $normalized = [];
        foreach ($flights as $flight) {
            $flight = preg_replace('/\s+/', ' ', trim(wp_strip_all_tags((string) $flight)));
            if ($flight === '') {
                continue;
            }

            $key = strtolower($flight);
            if (!isset($normalized[$key])) {
                $normalized[$key] = $flight;
            }
        }

        return array_values($normalized);
    }

    private static function normalize_event_title(string $title): string {
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', strtolower($title)));
    }

    private static function build_signup_payload(WP_Post $signup, string $secret): array {
        $event_id = absint(get_post_meta($signup->ID, self::SIGNUP_META_EVENT_ID, true));
        $event = $event_id ? get_post($event_id) : null;
        $start = $event_id ? (string) get_post_meta($event_id, self::META_START, true) : '';
        $end = $event_id ? (string) get_post_meta($event_id, self::META_END, true) : '';
        $location = $event_id ? (string) get_post_meta($event_id, self::META_LOCATION, true) : '';

        return [
            'secret' => $secret,
            'action' => 'event_signup_upsert',
            'signup' => [
                'signup_id' => (string) $signup->ID,
                'submitted_at' => get_post_time('Y-m-d H:i:s', false, $signup),
                'event_id' => (string) $event_id,
                'event_title' => (string) get_post_meta($signup->ID, self::SIGNUP_META_EVENT_TITLE, true),
                'event_start' => $start,
                'event_end' => $end,
                'event_location' => $location,
                'event_url' => $event ? get_permalink($event) : '',
                'name' => (string) get_post_meta($signup->ID, self::SIGNUP_META_NAME, true),
                'email' => (string) get_post_meta($signup->ID, self::SIGNUP_META_EMAIL, true),
                'flight' => (string) get_post_meta($signup->ID, self::SIGNUP_META_FLIGHT, true),
                'team' => (string) get_post_meta($signup->ID, self::SIGNUP_META_TEAM, true),
                'team_members' => (string) get_post_meta($signup->ID, self::SIGNUP_META_TEAM_MEMBERS, true),
                'notes' => (string) get_post_meta($signup->ID, self::SIGNUP_META_NOTES, true),
            ],
        ];
    }

    private static function mark_signup_sync_failed(int $signup_id, string $message): void {
        update_post_meta($signup_id, self::SIGNUP_META_SYNC_STATUS, 'failed');
        update_post_meta($signup_id, self::SIGNUP_META_SYNC_ERROR, sanitize_text_field($message));
        error_log('TN event signup sync failed for signup ' . $signup_id . ': ' . $message);
    }

    private static function save_meta(int $post_id, string $meta_key, string $field, callable $sanitize): void {
        if (!isset($_POST[$field])) {
            delete_post_meta($post_id, $meta_key);
            return;
        }

        $value = $sanitize(wp_unslash($_POST[$field]));
        if ($value === '') {
            delete_post_meta($post_id, $meta_key);
            return;
        }

        update_post_meta($post_id, $meta_key, $value);
    }

    private static function sanitize_datetime($value): string {
        $value = sanitize_text_field((string) $value);
        $date = self::parse_local_datetime($value);

        return $date ? $date->format('Y-m-d H:i:s') : '';
    }

    private static function sanitize_flight_options($value): string {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value);
        $lines = is_array($lines) ? $lines : [];
        $lines = array_filter(array_map(static function ($line): string {
            return sanitize_text_field($line);
        }, $lines));

        return implode("\n", array_values(array_unique($lines)));
    }

    private static function datetime_value(string $value): string {
        $date = self::parse_local_datetime($value);

        return $date ? $date->format('Y-m-d\TH:i') : '';
    }

    private static function format_datetime(string $value): string {
        $date = self::parse_local_datetime($value);
        if (!$date) {
            return $value;
        }

        return wp_date(get_option('date_format') . ' ' . get_option('time_format'), $date->getTimestamp(), wp_timezone());
    }

    private static function parse_local_datetime(string $value): ?DateTimeImmutable {
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d\TH:i', 'Y-m-d H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value, wp_timezone());
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        return null;
    }

    private static function schedule_css(): string {
        return '
.tn-event-schedule {
    display: grid;
    gap: 1.25rem;
}
.tn-event-card {
    border: 1px solid rgba(11, 28, 58, 0.14);
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}
.tn-event-card__image img,
.tn-event-card__description img {
    display: block;
    max-width: 100%;
    height: auto;
}
.tn-event-card__body {
    padding: 1.25rem;
}
.tn-event-card__title {
    margin: 0 0 0.5rem;
}
.tn-event-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.75rem;
    margin-bottom: 1rem;
    color: #50575e;
    font-size: 0.95rem;
}
.tn-event-card__description > :first-child {
    margin-top: 0;
}
.tn-event-card__description > :last-child {
    margin-bottom: 0;
}
.tn-event-card__action {
    margin: 1rem 0 0;
}
.tn-event-signup {
    margin-top: 1.25rem;
    padding: 1rem;
    border: 1px solid rgba(11, 28, 58, 0.12);
    border-radius: 8px;
    background: #f8fafc;
}
.tn-event-signup__title {
    margin: 0 0 0.4rem;
}
.tn-event-signup__note,
.tn-event-signup__message {
    margin: 0 0 0.85rem;
}
.tn-event-signup__message--success {
    color: #0f6b38;
}
.tn-event-signup__message--error {
    color: #b42318;
}
.tn-event-signup__form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
}
.tn-event-signup__field,
.tn-event-signup__submit {
    margin: 0;
}
.tn-event-signup__field--full,
.tn-event-signup__submit {
    grid-column: 1 / -1;
}
.tn-event-signup__field label {
    display: block;
    margin-bottom: 0.25rem;
    font-weight: 700;
}
.tn-event-signup__field input,
.tn-event-signup__field select,
.tn-event-signup__field textarea {
    width: 100%;
    border: 1px solid rgba(11, 28, 58, 0.18);
    border-radius: 6px;
    padding: 0.55rem 0.65rem;
}
.tn-event-signup__submit button {
    border: 0;
    border-radius: 6px;
    padding: 0.65rem 1rem;
    cursor: pointer;
    font-weight: 700;
}
.tn-event-signup__trap {
    position: absolute;
    left: -9999px;
}
.tn-event-schedule-empty {
    color: #50575e;
}
@media (max-width: 640px) {
    .tn-event-signup__form {
        grid-template-columns: 1fr;
    }
}
';
    }
}

TN_Event_Schedule::init();
