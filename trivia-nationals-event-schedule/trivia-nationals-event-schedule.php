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
    private const META_START = '_tn_event_start';
    private const META_END = '_tn_event_end';
    private const META_LOCATION = '_tn_event_location';
    private const META_URL = '_tn_event_url';
    private const NONCE_ACTION = 'tn_event_schedule_save';
    private const NONCE_NAME = 'tn_event_schedule_nonce';

    public static function init(): void {
        add_action('init', [self::class, 'register_post_type']);
        add_action('add_meta_boxes', [self::class, 'add_event_details_box']);
        add_action('save_post_' . self::POST_TYPE, [self::class, 'save_event_details']);
        add_action('admin_head-post.php', [self::class, 'add_editor_note']);
        add_action('admin_head-post-new.php', [self::class, 'add_editor_note']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_shortcode('trivia_nationals_event_schedule', [self::class, 'render_schedule_shortcode']);
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
            </div>
        </article>
        <?php
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
.tn-event-schedule-empty {
    color: #50575e;
}
';
    }
}

TN_Event_Schedule::init();
