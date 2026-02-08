<?php
/**
 * Custom Post Type for Coming Soon Page
 *
 * Registers a private custom post type to store the Coming Soon page content.
 * Enforces a single-post constraint and provides default block content
 * including a heading, description paragraph, and countdown block.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages the Coming Soon page CPT registration and content provisioning.
 *
 * @since 1.0.0
 */
class Mudrava_Coming_Soon_Post_Type
{
    /**
     * Post type slug.
     *
     * @since 1.0.0
     * @var string
     */
    public const POST_TYPE = 'mudrava_cs_page';

    /**
     * Option key storing the Coming Soon page ID.
     *
     * @since 1.0.0
     * @var string
     */
    public const PAGE_ID_OPTION = 'mudrava_coming_soon_page_id';

    /**
     * Register the custom post type with WordPress.
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        register_post_type(self::POST_TYPE, [
            'label'               => __('Coming Soon Page', 'wp-coming-soon-by-mudrava'),
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'rest_base'           => 'mudrava-cs-pages',
            'supports'            => ['editor', 'revisions'],
            'has_archive'         => false,
            'rewrite'             => false,
            'capability_type'     => 'page',
            'map_meta_cap'        => true,
        ]);
    }

    /**
     * Ensure the Coming Soon page exists.
     *
     * Creates the page with default block content if it doesn't exist.
     *
     * @since 1.0.0
     */
    public function ensure_page_exists(): void
    {
        $page_id = self::get_page_id();

        if ($page_id > 0) {
            $post = get_post($page_id);

            if ($post) {
                /* If the page is in the trash, restore it automatically. */
                if ('trash' === $post->post_status) {
                    wp_untrash_post($page_id);
                    wp_update_post([
                        'ID'          => $page_id,
                        'post_status' => 'publish',
                    ]);
                }
                return;
            }
        }

        $default_content = $this->get_default_content();

        $new_page_id = wp_insert_post([
            'post_type'    => self::POST_TYPE,
            'post_title'   => __('Coming Soon', 'wp-coming-soon-by-mudrava'),
            'post_content' => $default_content,
            'post_status'  => 'publish',
        ]);

        if (!is_wp_error($new_page_id)) {
            update_option(self::PAGE_ID_OPTION, $new_page_id, true);
        }
    }

    /**
     * Retrieve the stored Coming Soon page ID.
     *
     * @since 1.0.0
     *
     * @return int Page ID or 0 if not set.
     */
    public static function get_page_id(): int
    {
        return (int) get_option(self::PAGE_ID_OPTION, 0);
    }

    /**
     * Generate default block content for a new Coming Soon page.
     *
     * Includes a spacer, heading, paragraph, and countdown block with a
     * target date set 30 days in the future from the current time.
     *
     * @since 1.0.0
     *
     * @return string Block markup.
     */
    private function get_default_content(): string
    {
        $target_date = gmdate('Y-m-d\TH:i:s', time() + (30 * DAY_IN_SECONDS));

        return <<<BLOCKS
<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">We Are Coming Soon</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Our website is under construction. We will be here soon with a new experience.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:mudrava/countdown {"targetDate":"{$target_date}","showDays":true,"showHours":true,"showMinutes":true,"showSeconds":true} /-->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
BLOCKS;
    }
}
