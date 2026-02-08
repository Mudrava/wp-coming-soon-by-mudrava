<?php
/**
 * Plugin Name:       Coming Soon by Mudrava
 * Plugin URI:        https://github.com/Mudrava/wp-coming-soon-by-mudrava
 * Description:       Lightweight Coming Soon & Maintenance Mode page with a Gutenberg countdown block, REST-based settings, and GitHub auto-updates.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            Mudrava
 * Author URI:        https://mudrava.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-coming-soon-by-mudrava
 * Domain Path:       /languages
 * Update URI:        https://github.com/Mudrava/wp-coming-soon-by-mudrava
 *
 * @package Mudrava\ComingSoon
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Current plugin version.
 *
 * @var string
 */
define('MUDRAVA_COMING_SOON_VERSION', '1.0.0');

/**
 * Absolute path to the plugin directory (with trailing slash).
 *
 * @var string
 */
define('MUDRAVA_COMING_SOON_PATH', plugin_dir_path(__FILE__));

/**
 * Public URL to the plugin directory (with trailing slash).
 *
 * @var string
 */
define('MUDRAVA_COMING_SOON_URL', plugin_dir_url(__FILE__));

/**
 * Full path to the main plugin file.
 *
 * @var string
 */
define('MUDRAVA_COMING_SOON_FILE', __FILE__);

/**
 * Autoload plugin classes from the includes directory.
 *
 * @since 1.0.0
 */
spl_autoload_register(function (string $class_name): void {
    $prefix = 'Mudrava_Coming_Soon';

    if (!str_starts_with($class_name, $prefix)) {
        return;
    }

    $class_file = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    $file_path  = MUDRAVA_COMING_SOON_PATH . 'includes/' . $class_file;

    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

/**
 * Run plugin activation routines.
 *
 * @since 1.0.0
 */
function mudrava_coming_soon_activate(): void
{
    $settings = Mudrava_Coming_Soon_Settings::get_instance();
    $settings->ensure_defaults();

    $post_type = new Mudrava_Coming_Soon_Post_Type();
    $post_type->register();
    $post_type->ensure_page_exists();

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'mudrava_coming_soon_activate');

/**
 * Run plugin deactivation routines.
 *
 * @since 1.0.0
 */
function mudrava_coming_soon_deactivate(): void
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'mudrava_coming_soon_deactivate');

/**
 * Initialize the plugin after all plugins have loaded.
 *
 * @since 1.0.0
 */
function mudrava_coming_soon_init(): void
{
    load_plugin_textdomain(
        'wp-coming-soon-by-mudrava',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );

    Mudrava_Coming_Soon::get_instance()->boot();
}
add_action('plugins_loaded', 'mudrava_coming_soon_init');
