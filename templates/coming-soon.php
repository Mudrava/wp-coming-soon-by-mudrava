<?php
/**
 * Coming Soon Page Template
 *
 * Standalone HTML template rendered when Coming Soon mode is active.
 * Variables are extracted from the $vars array in Mudrava_Coming_Soon_Frontend.
 *
 * Available variables:
 * @var string $page_title                 Page <title>
 * @var string $meta_description           Meta description content
 * @var string $content                    Rendered block content (HTML)
 * @var string $background_color           Hex background colour
 * @var string $background_image           Background image URL
 * @var int    $background_blur            Blur amount in pixels (0–20)
 * @var string $background_overlay_color   Overlay hex colour
 * @var int    $background_overlay_opacity Overlay opacity percentage (0–100)
 * @var string $background_size            CSS background-size value
 * @var string $background_position        CSS background-position value
 * @var string $custom_css                 User custom CSS
 * @var array  $social_links               Social link entries
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* Compute overlay rgba value. */
$overlay_rgba = 'transparent';
if ($background_overlay_opacity > 0 && !empty($background_overlay_color)) {
    $hex = ltrim($background_overlay_color, '#');
    $r   = hexdec(substr($hex, 0, 2));
    $g   = hexdec(substr($hex, 2, 2));
    $b   = hexdec(substr($hex, 4, 2));
    $a   = round($background_overlay_opacity / 100, 2);
    $overlay_rgba = "rgba({$r},{$g},{$b},{$a})";
}

/* Determine whether an overlay or blur is needed. */
$has_overlay = ($background_overlay_opacity > 0 || $background_blur > 0) && !empty($background_image);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <?php if (!empty($meta_description)) : ?>
        <meta name="description" content="<?php echo $meta_description; ?>">
    <?php endif; ?>
    <title><?php echo $page_title; ?></title>

    <style>
        :root {
            --mcs-bg-color: <?php echo $background_color; ?>;
            --mcs-bg-image: <?php echo !empty($background_image) ? 'url(' . esc_url($background_image) . ')' : 'none'; ?>;
            --mcs-bg-size: <?php echo esc_attr($background_size); ?>;
            --mcs-bg-position: <?php echo esc_attr($background_position); ?>;
            --mcs-overlay: <?php echo $overlay_rgba; ?>;
            --mcs-bg-blur: <?php echo (int) $background_blur; ?>px;
        }
    </style>

    <?php wp_head(); ?>

    <?php if (!empty($custom_css)) : ?>
        <style id="mudrava-coming-soon-custom-css"><?php echo $custom_css; ?></style>
    <?php endif; ?>
</head>
<body class="mudrava-coming-soon-body">

    <?php if ($has_overlay) : ?>
        <div class="mudrava-coming-soon-overlay" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="mudrava-coming-soon-wrapper">
        <main class="mudrava-coming-soon-content">
            <?php echo $content; ?>
        </main>

        <?php if (!empty($social_links) && is_array($social_links)) : ?>
            <?php
            $active_links = array_filter($social_links, static function ($link) {
                return !empty($link['url']);
            });
            ?>
            <?php if (!empty($active_links)) : ?>
                <nav class="mudrava-coming-soon-social" aria-label="<?php esc_attr_e('Social Links', 'wp-coming-soon-by-mudrava'); ?>">
                    <?php foreach ($active_links as $link) :
                        $platform = $link['platform'] ?? 'link';
                        $label    = !empty($link['label']) ? $link['label'] : ucfirst($platform);
                        $url      = esc_url($link['url']);
                    ?>
                        <a href="<?php echo $url; ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="mudrava-coming-soon-social__link mudrava-coming-soon-social__link--<?php echo esc_attr($platform); ?>">
                            <?php echo esc_html($label); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer class="mudrava-coming-soon-credit">
        <a href="https://mudrava.com" target="_blank" rel="noopener">Coming Soon by Mudrava</a>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>
