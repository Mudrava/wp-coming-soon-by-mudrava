<?php
/**
 * GitHub Updater
 *
 * Enables automatic plugin updates from a public GitHub repository by hooking
 * into the WordPress plugin update system. Checks the GitHub Releases API
 * for the latest version and, when a newer version is available, provides the
 * download URL to WordPress's built-in updater.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Integrates with WordPress update system for GitHub-hosted releases.
 *
 * @since 1.0.0
 */
class Mudrava_Coming_Soon_GitHub_Updater
{
    /**
     * GitHub repository owner/name.
     *
     * @since 1.0.0
     * @var string
     */
    private string $repo;

    /**
     * Plugin basename (relative to plugins directory).
     *
     * @since 1.0.0
     * @var string
     */
    private string $basename;

    /**
     * Current plugin version.
     *
     * @since 1.0.0
     * @var string
     */
    private string $version;

    /**
     * Plugin slug derived from the basename.
     *
     * @since 1.0.0
     * @var string
     */
    private string $slug;

    /**
     * Transient key for caching release data.
     *
     * @since 1.0.0
     * @var string
     */
    private string $transient_key;

    /**
     * Cache lifetime in seconds (6 hours).
     *
     * @since 1.0.0
     * @var int
     */
    private const CACHE_TTL = 21600;

    /**
     * Initialize the updater.
     *
     * @since 1.0.0
     *
     * @param string $repo     Repository in "owner/repo" format.
     * @param string $basename Plugin basename (e.g. "my-plugin/my-plugin.php").
     * @param string $version  Current installed version.
     */
    public function __construct(string $repo, string $basename, string $version)
    {
        $this->repo          = $repo;
        $this->basename      = $basename;
        $this->version       = $version;
        $this->slug          = dirname($basename);
        $this->transient_key = 'mudrava_cs_github_update_' . md5($repo);
    }

    /**
     * Register WordPress filter hooks for the update system.
     *
     * @since 1.0.0
     */
    public function init(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'post_install'], 10, 3);
    }

    /**
     * Check GitHub for a newer release and inject update data.
     *
     * @since 1.0.0
     *
     * @param object $transient WordPress update transient.
     * @return object Modified transient with update data if available.
     */
    public function check_for_update(object $transient): object
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_latest_release();

        if (!$release) {
            return $transient;
        }

        $remote_version = ltrim($release['tag_name'] ?? '', 'v');
        $download_url   = $release['download_url'] ?? '';

        if (empty($remote_version) || empty($download_url)) {
            return $transient;
        }

        if (version_compare($remote_version, $this->version, '>')) {
            $transient->response[$this->basename] = (object) [
                'slug'        => $this->slug,
                'plugin'      => $this->basename,
                'new_version' => $remote_version,
                'url'         => "https://github.com/{$this->repo}",
                'package'     => $download_url,
            ];
        } else {
            $transient->no_update[$this->basename] = (object) [
                'slug'        => $this->slug,
                'plugin'      => $this->basename,
                'new_version' => $remote_version,
                'url'         => "https://github.com/{$this->repo}",
                'package'     => $download_url,
            ];
        }

        return $transient;
    }

    /**
     * Provide plugin information for the "View Details" modal.
     *
     * @since 1.0.0
     *
     * @param false|object|array $result Result to pass through if not handling.
     * @param string             $action API action (should be "plugin_information").
     * @param object             $args   Arguments including "slug".
     * @return false|object
     */
    public function plugin_info($result, string $action, object $args)
    {
        if ('plugin_information' !== $action) {
            return $result;
        }

        if (($args->slug ?? '') !== $this->slug) {
            return $result;
        }

        $release = $this->get_latest_release();

        if (!$release) {
            return $result;
        }

        $remote_version = ltrim($release['tag_name'] ?? '', 'v');

        return (object) [
            'name'            => 'Coming Soon by Mudrava',
            'slug'            => $this->slug,
            'version'         => $remote_version,
            'author'          => '<a href="https://mudrava.com">Mudrava</a>',
            'homepage'        => "https://github.com/{$this->repo}",
            'requires'        => '6.2',
            'tested'          => '',
            'requires_php'    => '8.0',
            'download_link'   => $release['download_url'] ?? '',
            'sections'        => [
                'description' => $release['body'] ?? '',
            ],
        ];
    }

    /**
     * Ensure the plugin directory name is correct after installation.
     *
     * GitHub ZIP archives use "repo-tag" naming which doesn't match the
     * expected plugin directory name. This hook renames the extracted
     * directory to the correct slug.
     *
     * @since 1.0.0
     *
     * @param bool  $response   Installation result.
     * @param array $hook_extra Extra data about the installation.
     * @param array $result     Installation result details.
     * @return array Modified result with corrected destination.
     */
    public function post_install($response, array $hook_extra, array $result): array
    {
        global $wp_filesystem;

        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->basename) {
            return $result;
        }

        $proper_destination = WP_PLUGIN_DIR . '/' . $this->slug;

        $wp_filesystem->move($result['destination'], $proper_destination);

        $result['destination']      = $proper_destination;
        $result['destination_name'] = $this->slug;
        $result['remote_destination'] = $proper_destination;

        /* Re-activate if it was active before. */
        activate_plugin($this->basename);

        return $result;
    }

    /**
     * Fetch the latest release from GitHub with transient caching.
     *
     * @since 1.0.0
     *
     * @return array|null Release data or null on failure.
     */
    private function get_latest_release(): ?array
    {
        $cached = get_transient($this->transient_key);

        if (false !== $cached) {
            return is_array($cached) ? $cached : null;
        }

        $url = "https://api.github.com/repos/{$this->repo}/releases/latest";

        $response = wp_remote_get($url, [
            'timeout'    => 10,
            'headers'    => [
                'Accept' => 'application/vnd.github.v3+json',
            ],
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
        ]);

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            /* Cache the failure to avoid hammering the API. */
            set_transient($this->transient_key, 'error', self::CACHE_TTL);
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($body) || empty($body['tag_name'])) {
            set_transient($this->transient_key, 'error', self::CACHE_TTL);
            return null;
        }

        /* Prefer .zip asset, fall back to GitHub source zipball. */
        $download_url = $body['zipball_url'] ?? '';

        if (!empty($body['assets']) && is_array($body['assets'])) {
            foreach ($body['assets'] as $asset) {
                if (
                    isset($asset['browser_download_url'])
                    && str_ends_with($asset['browser_download_url'], '.zip')
                ) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }

        $release_data = [
            'tag_name'     => $body['tag_name'],
            'body'         => $body['body'] ?? '',
            'download_url' => $download_url,
        ];

        set_transient($this->transient_key, $release_data, self::CACHE_TTL);

        return $release_data;
    }
}
