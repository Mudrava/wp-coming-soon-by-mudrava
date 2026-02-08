<?php
/**
 * Access Control
 *
 * Determines whether the Coming Soon page should be displayed for the current
 * request based on mode status, user role, IP whitelist, preview token, and
 * launch date. Acts as the single authoritative decision point for access control.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Evaluates whether the current request should see the Coming Soon page.
 *
 * @since 1.0.0
 */
class Mudrava_Coming_Soon_Access
{
    /**
     * Cookie name used to persist preview access across requests.
     *
     * @since 1.0.0
     * @var string
     */
    private const PREVIEW_COOKIE = 'mudrava_cs_preview';

    /**
     * Preview cookie lifetime in seconds (24 hours).
     *
     * @since 1.0.0
     * @var int
     */
    private const PREVIEW_COOKIE_TTL = 86400;

    /**
     * Determine if the Coming Soon page should be rendered for the current request.
     *
     * Decision hierarchy (first match wins, returns false = bypass):
     * 1. Mode disabled                 -> bypass
     * 2. System request (admin, REST, AJAX, cron, wp-login) -> bypass
     * 3. Valid preview token            -> bypass (sets cookie)
     * 4. Active preview cookie          -> bypass
     * 5. Logged-in user with bypass role -> bypass
     * 6. Whitelisted IP                 -> bypass
     * 7. Launch date has passed         -> bypass (if use_launch_date enabled)
     * 8. Otherwise                      -> show Coming Soon
     *
     * @since 1.0.0
     *
     * @return bool True if the Coming Soon page should be shown.
     */
    public function should_show_coming_soon(): bool
    {
        $settings = Mudrava_Coming_Soon_Settings::get_instance()->get_all();

        /* 1. Mode must be explicitly enabled. */
        if (empty($settings['enabled'])) {
            return false;
        }

        /* 2. Never intercept system requests. */
        if ($this->is_system_request()) {
            return false;
        }

        /* 3. Preview token in URL — grant access and set cookie. */
        if ($this->has_valid_preview_token($settings['preview_token'] ?? '')) {
            $this->set_preview_cookie($settings['preview_token']);
            return false;
        }

        /* 4. Active preview cookie from a prior token-based visit. */
        if ($this->has_preview_cookie($settings['preview_token'] ?? '')) {
            return false;
        }

        /* 5. Logged-in user with a bypass-eligible role. */
        if ($this->user_has_bypass_role($settings['bypass_roles'] ?? [])) {
            return false;
        }

        /* 6. Client IP matches the whitelist. */
        if ($this->is_ip_whitelisted($settings['ip_whitelist'] ?? [])) {
            return false;
        }

        /* 7. Launch date has passed — auto-bypass (mode remains enabled in settings). */
        $use_launch = $settings['use_launch_date'] ?? true;
        if ($use_launch && $this->has_launch_date_passed($settings['launch_date'] ?? '')) {
            return false;
        }

        /**
         * Filter the final access decision.
         *
         * Allows third-party plugins to override the Coming Soon visibility
         * for specific conditions not covered by the built-in checks.
         *
         * @since 1.0.0
         *
         * @param bool  $show     Whether to show the Coming Soon page. Default true.
         * @param array $settings Current plugin settings.
         */
        return (bool) apply_filters('mudrava_coming_soon_access', true, $settings);
    }

    /**
     * Check if the current request is a WordPress system request.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function is_system_request(): bool
    {
        /* Admin area, AJAX, REST API, cron. */
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return true;
        }

        /* REST API requests. */
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        /* wp-login.php and wp-register.php. */
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (
            str_contains($script, 'wp-login.php')
            || str_contains($script, 'wp-register.php')
            || str_contains($script, 'wp-signup.php')
        ) {
            return true;
        }

        /* Robots.txt and favicon (commonly requested by browsers/crawlers). */
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (
            str_contains($request_uri, '/robots.txt')
            || str_contains($request_uri, '/favicon.ico')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if a valid preview token is present in the query string.
     *
     * @since 1.0.0
     *
     * @param string $expected_token Stored preview token.
     * @return bool
     */
    private function has_valid_preview_token(string $expected_token): bool
    {
        if (empty($expected_token)) {
            return false;
        }

        $token = $_GET['mudrava_preview'] ?? '';

        return !empty($token) && hash_equals($expected_token, sanitize_text_field($token));
    }

    /**
     * Set a preview cookie to maintain bypass access.
     *
     * @since 1.0.0
     *
     * @param string $token Preview token to hash for cookie value.
     */
    private function set_preview_cookie(string $token): void
    {
        $cookie_value = hash('sha256', $token);

        setcookie(
            self::PREVIEW_COOKIE,
            $cookie_value,
            [
                'expires'  => time() + self::PREVIEW_COOKIE_TTL,
                'path'     => '/',
                'secure'   => is_ssl(),
                'httponly'  => true,
                'samesite' => 'Lax',
            ]
        );

        /* Also set in the current request for immediate effect. */
        $_COOKIE[self::PREVIEW_COOKIE] = $cookie_value;
    }

    /**
     * Check if a valid preview cookie exists.
     *
     * @since 1.0.0
     *
     * @param string $token Current preview token.
     * @return bool
     */
    private function has_preview_cookie(string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $cookie = $_COOKIE[self::PREVIEW_COOKIE] ?? '';

        return !empty($cookie) && hash_equals(hash('sha256', $token), $cookie);
    }

    /**
     * Check if the current user has a role that bypasses the Coming Soon page.
     *
     * @since 1.0.0
     *
     * @param string[] $bypass_roles Allowed role slugs.
     * @return bool
     */
    private function user_has_bypass_role(array $bypass_roles): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();

        foreach ($user->roles as $role) {
            if (in_array($role, $bypass_roles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the client IP appears in the whitelist.
     *
     * @since 1.0.0
     *
     * @param string[] $whitelist IP addresses and CIDR ranges.
     * @return bool
     */
    private function is_ip_whitelisted(array $whitelist): bool
    {
        if (empty($whitelist)) {
            return false;
        }

        $client_ip = $this->get_client_ip();

        if (empty($client_ip)) {
            return false;
        }

        foreach ($whitelist as $entry) {
            if ($client_ip === $entry) {
                return true;
            }

            /* CIDR range check. */
            if (str_contains($entry, '/') && $this->ip_in_cidr($client_ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the client's IP address.
     *
     * Checks common proxy headers first, then falls back to REMOTE_ADDR.
     *
     * @since 1.0.0
     *
     * @return string Client IP address.
     */
    private function get_client_ip(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            $value = $_SERVER[$header] ?? '';

            if (empty($value)) {
                continue;
            }

            /* X-Forwarded-For may contain a comma-separated list. */
            if (str_contains($value, ',')) {
                $value = trim(explode(',', $value)[0]);
            }

            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }

        return '';
    }

    /**
     * Check if an IP address falls within a CIDR range.
     *
     * @since 1.0.0
     *
     * @param string $ip   IP address.
     * @param string $cidr CIDR range (e.g. "192.168.1.0/24").
     * @return bool
     */
    private function ip_in_cidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr, 2);

        $mask = (int) $mask;

        /* IPv4. */
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip_long     = ip2long($ip);
            $subnet_long = ip2long($subnet);

            if (false === $ip_long || false === $subnet_long) {
                return false;
            }

            $mask_long = -1 << (32 - $mask);

            return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
        }

        return false;
    }

    /**
     * Check if the launch date has passed.
     *
     * @since 1.0.0
     *
     * @param string $launch_date ISO 8601 date string.
     * @return bool
     */
    private function has_launch_date_passed(string $launch_date): bool
    {
        if (empty($launch_date)) {
            return false;
        }

        $launch_timestamp = strtotime($launch_date);

        if (false === $launch_timestamp) {
            return false;
        }

        return time() >= $launch_timestamp;
    }
}
