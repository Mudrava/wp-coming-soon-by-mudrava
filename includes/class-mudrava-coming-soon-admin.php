<?php
/**
 * Admin Interface
 *
 * Registers the plugin settings page under the WordPress admin menu,
 * enqueues the React settings application, displays admin bar indicators,
 * and shows dashboard/plugin notifications when Coming Soon mode is active.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages all admin-facing functionality.
 *
 * @since 1.0.0
 */
class Mudrava_Coming_Soon_Admin
{
    /**
     * Settings page slug.
     *
     * @since 1.0.0
     * @var string
     */
    private const MENU_SLUG = 'mudrava-coming-soon';

    /**
     * Register WordPress hooks for the admin area.
     *
     * @since 1.0.0
     */
    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_bar_menu', [$this, 'admin_bar_indicator'], 100);
        add_action('admin_notices', [$this, 'admin_notice']);
    }

    /**
     * Register the settings page under the main admin menu.
     *
     * @since 1.0.0
     */
    public function register_menu(): void
    {
        add_menu_page(
            __('Coming Soon', 'wp-coming-soon-by-mudrava'),
            __('Coming Soon', 'wp-coming-soon-by-mudrava'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_page'],
            'dashicons-clock',
            80
        );
    }

    /**
     * Render the settings page container.
     *
     * The actual UI is rendered by the React application into the #mudrava-coming-soon-root div.
     *
     * @since 1.0.0
     */
    public function render_page(): void
    {
        echo '<div id="mudrava-coming-soon-root"></div>';
    }

    /**
     * Enqueue React settings application scripts and styles.
     *
     * Only loads on the plugin's own settings page to avoid conflicts.
     *
     * @since 1.0.0
     *
     * @param string $hook_suffix Admin page hook suffix.
     */
    public function enqueue_assets(string $hook_suffix): void
    {
        if ('toplevel_page_' . self::MENU_SLUG !== $hook_suffix) {
            return;
        }

        $asset_path = MUDRAVA_COMING_SOON_PATH . 'build/settings/index.asset.php';
        $asset      = file_exists($asset_path)
            ? require $asset_path
            : ['dependencies' => [], 'version' => MUDRAVA_COMING_SOON_VERSION];

        wp_enqueue_style(
            'mudrava-coming-soon-settings',
            MUDRAVA_COMING_SOON_URL . 'build/settings/style-index.css',
            ['wp-components'],
            $asset['version']
        );

        wp_enqueue_script(
            'mudrava-coming-soon-settings',
            MUDRAVA_COMING_SOON_URL . 'build/settings/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        /* Load the WordPress media frame for background image selection. */
        wp_enqueue_media();

        /* Editor styles for block preview (if any). */
        wp_enqueue_style('wp-edit-blocks');

        $page_id  = Mudrava_Coming_Soon_Post_Type::get_page_id();
        $settings = Mudrava_Coming_Soon_Settings::get_instance()->get_all();

        $preview_url = add_query_arg(
            'mudrava_preview',
            $settings['preview_token'] ?? '',
            home_url('/')
        );

        wp_localize_script(
            'mudrava-coming-soon-settings',
            'mudravaComingSoon',
            [
                'restUrl'     => rest_url('mudrava/coming-soon/v1/'),
                'nonce'       => wp_create_nonce('wp_rest'),
                'editPageUrl' => $this->get_edit_page_url($page_id),
                'previewUrl'  => $preview_url,
                'version'     => MUDRAVA_COMING_SOON_VERSION,
                'roles'       => $this->get_available_roles(),
            ]
        );
    }

    /**
     * Add Coming Soon indicator to the admin bar.
     *
     * Shows a red "Coming Soon: ON" node when the mode is enabled so
     * administrators have a clear visual indication.
     *
     * @since 1.0.0
     *
     * @param \WP_Admin_Bar $admin_bar WordPress admin bar instance.
     */
    public function admin_bar_indicator(\WP_Admin_Bar $admin_bar): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = Mudrava_Coming_Soon_Settings::get_instance()->get_all();

        if (empty($settings['enabled'])) {
            return;
        }

        $show_bar = $settings['show_admin_bar'] ?? true;

        if (!$show_bar) {
            return;
        }

        $admin_bar->add_node([
            'id'    => 'mudrava-coming-soon-status',
            'title' => sprintf(
                '<span style="background:#e53e3e;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">%s</span>',
                __('Coming Soon: ON', 'wp-coming-soon-by-mudrava')
            ),
            'href'  => admin_url('admin.php?page=' . self::MENU_SLUG),
        ]);
    }

    /**
     * Display admin notice when Coming Soon mode is active.
     *
     * Shows on the dashboard and plugins pages to remind administrators
     * that the site is not publicly accessible.
     *
     * @since 1.0.0
     */
    public function admin_notice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $screen = get_current_screen();

        if (!$screen || !in_array($screen->id, ['dashboard', 'plugins'], true)) {
            return;
        }

        $settings = Mudrava_Coming_Soon_Settings::get_instance()->get_all();

        if (empty($settings['enabled'])) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
            esc_html__('Coming Soon mode is currently active. Your site is not visible to the public.', 'wp-coming-soon-by-mudrava'),
            esc_url(admin_url('admin.php?page=' . self::MENU_SLUG)),
            esc_html__('Manage Settings', 'wp-coming-soon-by-mudrava')
        );
    }

    /**
     * Get available WordPress roles for the bypass roles setting.
     *
     * @since 1.0.0
     *
     * @return array<string, string> Associative array of role slug => role name.
     */
    private function get_available_roles(): array
    {
        $wp_roles = wp_roles();
        $roles    = [];

        foreach ($wp_roles->role_names as $slug => $name) {
            $roles[$slug] = translate_user_role($name);
        }

        return $roles;
    }

    /**
     * Get the edit URL for the Coming Soon page, handling trashed/missing states.
     *
     * If the page is trashed, it will be restored automatically.
     * If the page does not exist, returns empty string (boot will recreate it on next load).
     *
     * @since 1.0.0
     *
     * @param int $page_id Coming Soon page ID.
     * @return string Edit URL or empty string.
     */
    private function get_edit_page_url(int $page_id): string
    {
        if ($page_id <= 0) {
            return '';
        }

        $post = get_post($page_id);

        if (!$post) {
            return '';
        }

        /* Auto-restore trashed pages so the edit link always works. */
        if ('trash' === $post->post_status) {
            wp_untrash_post($page_id);
            wp_update_post([
                'ID'          => $page_id,
                'post_status' => 'publish',
            ]);
        }

        return admin_url('post.php?post=' . $page_id . '&action=edit');
    }
}
