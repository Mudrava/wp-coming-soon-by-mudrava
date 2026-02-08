<?php
/**
 * Frontend Renderer
 *
 * Intercepts public-facing requests when Coming Soon mode is active and renders
 * the standalone Coming Soon page with appropriate HTTP headers. The page content
 * is sourced from the CPT post and rendered through the block rendering pipeline.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the frontend display of the Coming Soon page.
 *
 * @since 1.0.0
 */
class Mudrava_Coming_Soon_Frontend
{
    /**
     * Access control instance.
     *
     * @since 1.0.0
     * @var Mudrava_Coming_Soon_Access
     */
    private Mudrava_Coming_Soon_Access $access;

    /**
     * Initialize with an access control instance.
     *
     * @since 1.0.0
     *
     * @param Mudrava_Coming_Soon_Access $access Access control handler.
     */
    public function __construct(Mudrava_Coming_Soon_Access $access)
    {
        $this->access = $access;
    }

    /**
     * Register WordPress hooks for frontend interception.
     *
     * @since 1.0.0
     */
    public function init(): void
    {
        add_action('template_redirect', [$this, 'maybe_render'], 1);
    }

    /**
     * Check access and render the Coming Soon page if applicable.
     *
     * Sends HTTP 503 status with Retry-After header, sets cache control headers
     * to prevent proxy/CDN caching, and outputs the standalone template.
     *
     * @since 1.0.0
     */
    public function maybe_render(): void
    {
        if (!$this->access->should_show_coming_soon()) {
            return;
        }

        $settings = Mudrava_Coming_Soon_Settings::get_instance()->get_all();
        $page_id  = Mudrava_Coming_Soon_Post_Type::get_page_id();

        if ($page_id <= 0) {
            return;
        }

        $post = get_post($page_id);

        if (!$post || 'publish' !== $post->post_status) {
            return;
        }

        /* Set HTTP headers. */
        $retry_after = (int) ($settings['retry_after'] ?? 86400);

        status_header(503);
        header('Retry-After: ' . $retry_after);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex');

        /* Render block content through the standard WordPress pipeline. */
        $content = apply_filters('the_content', $post->post_content);

        /* Prepare template variables. */
        $template_vars = [
            'page_title'                 => esc_html($settings['page_title'] ?? 'Coming Soon'),
            'meta_description'           => esc_attr($settings['meta_description'] ?? ''),
            'content'                    => $content,
            'background_color'           => sanitize_hex_color($settings['background_color'] ?? '#0f172a') ?: '#0f172a',
            'background_image'           => esc_url($settings['background_image'] ?? ''),
            'background_blur'            => (int) ($settings['background_blur'] ?? 0),
            'background_overlay_color'   => sanitize_hex_color($settings['background_overlay_color'] ?? '#000000') ?: '#000000',
            'background_overlay_opacity' => (int) ($settings['background_overlay_opacity'] ?? 0),
            'background_size'            => sanitize_text_field($settings['background_size'] ?? 'cover'),
            'background_position'        => sanitize_text_field($settings['background_position'] ?? 'center center'),
            'custom_css'                 => wp_strip_all_tags($settings['custom_css'] ?? ''),
            'social_links'               => $settings['social_links'] ?? [],
        ];

        $this->render_template($template_vars);
        exit;
    }

    /**
     * Locate and render the Coming Soon template.
     *
     * Checks the active theme for a template override at
     * mudrava-coming-soon/coming-soon.php before falling back to the
     * plugin's bundled template.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $vars Template variables.
     */
    private function render_template(array $vars): void
    {
        /*
         * Enqueue block-related styles and scripts so that blocks render
         * correctly in the standalone template context.
         */
        wp_enqueue_global_styles();

        $frontend_asset_path = MUDRAVA_COMING_SOON_PATH . 'build/frontend/coming-soon.asset.php';
        $frontend_deps       = file_exists($frontend_asset_path)
            ? require $frontend_asset_path
            : ['dependencies' => [], 'version' => MUDRAVA_COMING_SOON_VERSION];

        wp_enqueue_style(
            'mudrava-coming-soon-frontend',
            MUDRAVA_COMING_SOON_URL . 'build/frontend/coming-soon.css',
            ['wp-block-library'],
            $frontend_deps['version']
        );

        /* Theme override path: {theme}/mudrava-coming-soon/coming-soon.php */
        $template = locate_template('mudrava-coming-soon/coming-soon.php');

        if (empty($template)) {
            $template = MUDRAVA_COMING_SOON_PATH . 'templates/coming-soon.php';
        }

        /**
         * Filter the Coming Soon template path.
         *
         * @since 1.0.0
         *
         * @param string $template Absolute path to the template file.
         * @param array  $vars     Template variables.
         */
        $template = apply_filters('mudrava_coming_soon_template', $template, $vars);

        if (file_exists($template)) {
            /* Extract variables into the template scope. */
            extract($vars, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            include $template;
        }
    }
}
