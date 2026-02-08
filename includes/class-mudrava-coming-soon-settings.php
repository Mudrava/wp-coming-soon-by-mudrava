<?php
/**
 * Plugin Settings Manager
 *
 * Manages persistent plugin configuration via a single serialized WordPress
 * option. Provides a REST API endpoint for the React-based settings interface
 * and static accessors for use throughout the plugin.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin settings storage, retrieval, validation, and REST API exposure.
 *
 * @since 1.0.0
 */
class Mudrava_Coming_Soon_Settings
{
    /**
     * WordPress option key for serialized settings.
     *
     * @since 1.0.0
     * @var string
     */
    public const OPTION_KEY = 'mudrava_coming_soon_options';

    /**
     * REST API namespace.
     *
     * @since 1.0.0
     * @var string
     */
    public const REST_NAMESPACE = 'mudrava/coming-soon/v1';

    /**
     * Singleton instance.
     *
     * @since 1.0.0
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Cached settings array.
     *
     * @since 1.0.0
     * @var array<string, mixed>|null
     */
    private ?array $cache = null;

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
     * Register REST API routes.
     *
     * @since 1.0.0
     */
    public function register_rest_routes(): void
    {
        register_rest_route(self::REST_NAMESPACE, '/settings', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'rest_get_settings'],
                'permission_callback' => [$this, 'rest_permissions_check'],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'rest_update_settings'],
                'permission_callback' => [$this, 'rest_permissions_check'],
                'args'                => $this->get_rest_args(),
            ],
        ]);
    }

    /**
     * Permission check for REST API settings endpoints.
     *
     * @since 1.0.0
     *
     * @return bool True if the current user can manage options.
     */
    public function rest_permissions_check(): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * Handle GET /settings — return all current settings.
     *
     * @since 1.0.0
     *
     * @return \WP_REST_Response
     */
    public function rest_get_settings(): \WP_REST_Response
    {
        return new \WP_REST_Response($this->get_all(), 200);
    }

    /**
     * Handle POST /settings — update settings from the React interface.
     *
     * Only keys present in the request body are updated; absent keys retain
     * their current values. Each value is sanitized according to its type.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Incoming REST request.
     * @return \WP_REST_Response Updated settings.
     */
    public function rest_update_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        $current = $this->get_all();
        $params  = $request->get_json_params();

        if (empty($params) || !is_array($params)) {
            return new \WP_REST_Response(['message' => 'Invalid request body.'], 400);
        }

        $sanitized = $this->sanitize_settings($params, $current);
        $this->save($sanitized);

        return new \WP_REST_Response($sanitized, 200);
    }

    /**
     * Retrieve all settings merged with defaults.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function get_all(): array
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        $stored = get_option(self::OPTION_KEY, []);

        if (!is_array($stored)) {
            $stored = [];
        }

        $this->cache = wp_parse_args($stored, self::get_defaults());

        return $this->cache;
    }

    /**
     * Retrieve a single setting value.
     *
     * @since 1.0.0
     *
     * @param string $key     Setting key.
     * @param mixed  $default Fallback value if key does not exist.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::get_instance()->get_all();
        $defaults = self::get_defaults();

        return $settings[$key] ?? $default ?? ($defaults[$key] ?? null);
    }

    /**
     * Persist settings to the database and invalidate cache.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $settings Complete settings array.
     */
    public function save(array $settings): void
    {
        update_option(self::OPTION_KEY, $settings, true);
        $this->cache = $settings;
    }

    /**
     * Populate default settings if the option does not yet exist.
     *
     * Called during plugin activation to ensure all required keys are present.
     *
     * @since 1.0.0
     */
    public function ensure_defaults(): void
    {
        $existing = get_option(self::OPTION_KEY, false);

        if (false === $existing) {
            $defaults = self::get_defaults();
            $defaults['preview_token'] = wp_generate_password(32, false);
            add_option(self::OPTION_KEY, $defaults, '', true);
        } elseif (is_array($existing) && empty($existing['preview_token'])) {
            $existing['preview_token'] = wp_generate_password(32, false);
            update_option(self::OPTION_KEY, $existing, true);
        }
    }

    /**
     * Default values for all plugin settings.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public static function get_defaults(): array
    {
        return [
            'enabled'                    => false,
            'use_launch_date'            => true,
            'launch_date'                => '',
            'page_title'                 => 'Coming Soon',
            'meta_description'           => '',
            'retry_after'                => 86400,
            'bypass_roles'               => ['administrator'],
            'ip_whitelist'               => [],
            'preview_token'              => '',
            'custom_css'                 => '',
            'background_color'           => '#0f172a',
            'background_image'           => '',
            'background_blur'            => 0,
            'background_overlay_color'   => '#000000',
            'background_overlay_opacity' => 0,
            'background_size'            => 'cover',
            'background_position'        => 'center center',
            'social_links'               => [],
            'show_admin_notice'          => true,
        ];
    }

    /**
     * Sanitize and validate incoming settings against their expected types.
     *
     * Merges sanitized input with the current stored values to produce a
     * complete settings array. Unknown keys are silently discarded.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $input   Raw input from the REST request.
     * @param array<string, mixed> $current Current stored settings.
     * @return array<string, mixed> Sanitized settings array.
     */
    private function sanitize_settings(array $input, array $current): array
    {
        $sanitized = $current;

        if (array_key_exists('enabled', $input)) {
            $sanitized['enabled'] = (bool) $input['enabled'];
        }

        if (array_key_exists('use_launch_date', $input)) {
            $sanitized['use_launch_date'] = (bool) $input['use_launch_date'];
        }

        if (array_key_exists('launch_date', $input)) {
            $sanitized['launch_date'] = sanitize_text_field((string) $input['launch_date']);
        }

        if (array_key_exists('page_title', $input)) {
            $sanitized['page_title'] = sanitize_text_field((string) $input['page_title']);
        }

        if (array_key_exists('meta_description', $input)) {
            $sanitized['meta_description'] = sanitize_textarea_field((string) $input['meta_description']);
        }

        if (array_key_exists('retry_after', $input)) {
            $retry = absint($input['retry_after']);
            $sanitized['retry_after'] = max(3600, min(2592000, $retry));
        }

        if (array_key_exists('bypass_roles', $input)) {
            $sanitized['bypass_roles'] = $this->sanitize_roles((array) $input['bypass_roles']);
        }

        if (array_key_exists('ip_whitelist', $input)) {
            $sanitized['ip_whitelist'] = $this->sanitize_ip_list((array) $input['ip_whitelist']);
        }

        if (array_key_exists('preview_token', $input)) {
            $token = sanitize_text_field((string) $input['preview_token']);
            if (!empty($token)) {
                $sanitized['preview_token'] = $token;
            }
        }

        if (array_key_exists('custom_css', $input)) {
            $sanitized['custom_css'] = wp_strip_all_tags((string) $input['custom_css']);
        }

        if (array_key_exists('background_color', $input)) {
            $color = sanitize_hex_color((string) $input['background_color']);
            if ($color) {
                $sanitized['background_color'] = $color;
            }
        }

        if (array_key_exists('background_image', $input)) {
            $sanitized['background_image'] = esc_url_raw((string) $input['background_image']);
        }

        if (array_key_exists('background_blur', $input)) {
            $blur = absint($input['background_blur']);
            $sanitized['background_blur'] = min(20, $blur);
        }

        if (array_key_exists('background_overlay_color', $input)) {
            $color = sanitize_hex_color((string) $input['background_overlay_color']);
            if ($color) {
                $sanitized['background_overlay_color'] = $color;
            }
        }

        if (array_key_exists('background_overlay_opacity', $input)) {
            $opacity = absint($input['background_overlay_opacity']);
            $sanitized['background_overlay_opacity'] = min(100, $opacity);
        }

        if (array_key_exists('background_size', $input)) {
            $allowed_sizes = ['cover', 'contain', 'auto'];
            $size = sanitize_text_field((string) $input['background_size']);
            if (in_array($size, $allowed_sizes, true)) {
                $sanitized['background_size'] = $size;
            }
        }

        if (array_key_exists('background_position', $input)) {
            $sanitized['background_position'] = sanitize_text_field((string) $input['background_position']);
        }

        if (array_key_exists('social_links', $input)) {
            $sanitized['social_links'] = $this->sanitize_social_links((array) $input['social_links']);
        }

        if (array_key_exists('show_admin_notice', $input)) {
            $sanitized['show_admin_notice'] = (bool) $input['show_admin_notice'];
        }

        return $sanitized;
    }

    /**
     * Validate role slugs against registered WordPress roles.
     *
     * Ensures 'administrator' is always included to prevent lockout.
     *
     * @since 1.0.0
     *
     * @param array $roles Raw role slug array.
     * @return string[] Validated role slugs.
     */
    private function sanitize_roles(array $roles): array
    {
        $wp_roles   = wp_roles()->get_names();
        $valid_keys = array_keys($wp_roles);
        $clean      = [];

        foreach ($roles as $role) {
            $role = sanitize_key((string) $role);
            if (in_array($role, $valid_keys, true)) {
                $clean[] = $role;
            }
        }

        if (!in_array('administrator', $clean, true)) {
            array_unshift($clean, 'administrator');
        }

        return array_unique($clean);
    }

    /**
     * Sanitize an array of IP addresses or CIDR ranges.
     *
     * @since 1.0.0
     *
     * @param array $ips Raw IP/CIDR array.
     * @return string[] Validated entries.
     */
    private function sanitize_ip_list(array $ips): array
    {
        $clean = [];

        foreach ($ips as $ip) {
            $ip = trim(sanitize_text_field((string) $ip));

            if (empty($ip)) {
                continue;
            }

            /* Accept plain IP addresses and CIDR notation. */
            if (
                filter_var($ip, FILTER_VALIDATE_IP)
                || preg_match('/^[\d.:a-fA-F]+\/\d{1,3}$/', $ip)
            ) {
                $clean[] = $ip;
            }
        }

        return array_unique($clean);
    }

    /**
     * Sanitize social link entries to URL-safe values.
     *
     * @since 1.0.0
     *
     * @param array $links Raw social link data.
     * @return array<int, array{platform: string, url: string, label: string}> Sanitized links.
     */
    private function sanitize_social_links(array $links): array
    {
        $clean = [];

        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }

            $platform = sanitize_key($link['platform'] ?? '');
            $url      = esc_url_raw($link['url'] ?? '');
            $label    = sanitize_text_field($link['label'] ?? '');

            if (!empty($platform) && !empty($url)) {
                $clean[] = [
                    'platform' => $platform,
                    'url'      => $url,
                    'label'    => $label,
                ];
            }
        }

        return $clean;
    }

    /**
     * Define REST API argument schema for the settings update endpoint.
     *
     * @since 1.0.0
     *
     * @return array<string, array> REST argument definitions.
     */
    private function get_rest_args(): array
    {
        return [
            'enabled' => [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
            ],
            'use_launch_date' => [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
            ],
            'launch_date' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'page_title' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'meta_description' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
            'retry_after' => [
                'type'    => 'integer',
                'minimum' => 3600,
                'maximum' => 2592000,
            ],
            'bypass_roles' => [
                'type'  => 'array',
                'items' => ['type' => 'string'],
            ],
            'ip_whitelist' => [
                'type'  => 'array',
                'items' => ['type' => 'string'],
            ],
            'preview_token' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'custom_css' => [
                'type' => 'string',
            ],
            'background_color' => [
                'type' => 'string',
            ],
            'background_image' => [
                'type' => 'string',
            ],
            'background_blur' => [
                'type'    => 'integer',
                'minimum' => 0,
                'maximum' => 20,
            ],
            'background_overlay_color' => [
                'type' => 'string',
            ],
            'background_overlay_opacity' => [
                'type'    => 'integer',
                'minimum' => 0,
                'maximum' => 100,
            ],
            'background_size' => [
                'type' => 'string',
            ],
            'background_position' => [
                'type' => 'string',
            ],
            'social_links' => [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'properties' => [
                        'platform' => ['type' => 'string'],
                        'url'      => ['type' => 'string'],
                        'label'    => ['type' => 'string'],
                    ],
                ],
            ],
            'show_admin_notice' => [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
            ],
        ];
    }
}
