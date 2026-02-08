<?php
/**
 * Main Plugin Orchestrator
 *
 * Central singleton that boots all plugin subsystems: settings, custom post type,
 * REST API, block registration, admin interface, and frontend rendering.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Orchestrates plugin initialization and subsystem boot sequence.
 *
 * @since 1.0.0
 */
class Mudrava_Coming_Soon
{
    /**
     * Singleton instance.
     *
     * @since 1.0.0
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Retrieve the singleton instance.
     *
     * @since 1.0.0
     *
     * @return self
     */
    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Boot all plugin subsystems.
     *
     * @since 1.0.0
     */
    public function boot(): void
    {
        /* Settings singleton (always needed). */
        $settings = Mudrava_Coming_Soon_Settings::get_instance();

        /* Custom Post Type registration. */
        $post_type = new Mudrava_Coming_Soon_Post_Type();
        add_action('init', [$post_type, 'register']);

        /* Ensure the Coming Soon page exists (handles re-installs / first boot). */
        add_action('init', [$post_type, 'ensure_page_exists'], 20);

        /* REST API routes for the React settings app. */
        add_action('rest_api_init', [$settings, 'register_rest_routes']);

        /* Gutenberg block registration. */
        add_action('init', [$this, 'register_blocks']);

        /* Admin interface (only in backend context). */
        if (is_admin()) {
            $admin = new Mudrava_Coming_Soon_Admin();
            $admin->init();

            $this->init_github_updater();
        }

        /* Frontend interception (only on public-facing requests). */
        if (!is_admin()) {
            $access   = new Mudrava_Coming_Soon_Access();
            $frontend = new Mudrava_Coming_Soon_Frontend($access);
            $frontend->init();
        }
    }

    /**
     * Register the countdown Gutenberg block.
     *
     * @since 1.0.0
     */
    public function register_blocks(): void
    {
        $block_json = MUDRAVA_COMING_SOON_PATH . 'build/blocks/countdown/block.json';

        if (file_exists($block_json)) {
            register_block_type($block_json, [
                'render_callback' => [$this, 'render_countdown_block'],
            ]);
        }
    }

    /**
     * Server-side render callback for the countdown block.
     *
     * Produces the static HTML shell that view.js hydrates on the frontend.
     *
     * @since 1.0.0
     *
     * @param array $attributes Block attributes from block.json.
     * @return string Rendered HTML.
     */
    public function render_countdown_block(array $attributes): string
    {
        $target_date = $attributes['targetDate'] ?? '';

        if (empty($target_date)) {
            return '';
        }

        $show = [
            'days'    => ! empty($attributes['showDays']),
            'hours'   => ! empty($attributes['showHours']),
            'minutes' => ! empty($attributes['showMinutes']),
            'seconds' => ! empty($attributes['showSeconds']),
        ];

        $labels = [
            'days'    => $attributes['labelDays']    ?? 'Days',
            'hours'   => $attributes['labelHours']   ?? 'Hours',
            'minutes' => $attributes['labelMinutes'] ?? 'Minutes',
            'seconds' => $attributes['labelSeconds'] ?? 'Seconds',
        ];

        $expired_message = $attributes['expiredMessage'] ?? 'We have launched!';

        $wrapper_attributes = get_block_wrapper_attributes([
            'data-target-date'     => esc_attr($target_date),
            'data-labels'          => wp_json_encode($labels),
            'data-expired-message' => esc_attr($expired_message),
        ]);

        $html  = '<div ' . $wrapper_attributes . '>';
        $html .= '<div class="wp-block-mudrava-countdown__grid">';

        foreach ($show as $unit => $visible) {
            if ($visible) {
                $html .= '<div class="wp-block-mudrava-countdown__unit" data-unit="' . esc_attr($unit) . '">';
                $html .= '<span class="wp-block-mudrava-countdown__number">00</span>';
                $html .= '<span class="wp-block-mudrava-countdown__label">' . esc_html($labels[$unit]) . '</span>';
                $html .= '</div>';
            }
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Initialize the GitHub updater for automatic plugin updates.
     *
     * Only loaded in admin context to avoid unnecessary API calls on
     * public-facing pages.
     *
     * @since 1.0.0
     */
    private function init_github_updater(): void
    {
        $updater = new Mudrava_Coming_Soon_GitHub_Updater(
            'Mudrava/wp-coming-soon-by-mudrava',
            plugin_basename(MUDRAVA_COMING_SOON_FILE),
            MUDRAVA_COMING_SOON_VERSION
        );
        $updater->init();
    }
}
