<?php
/**
 * Moves block content between Marketers Delight and Stackless.
 *
 * The two themes' blocks differ only in their namespace, so a migration rewrites the block
 * comment delimiters in post content: "wp:marketers-delight/<name>" to "wp:stackless/<name>" or
 * back. Nothing else in the content is touched: not the attributes, not text that mentions a
 * block name. Only blocks that exist in Stackless are converted; the rest are counted and left.
 * Before a post changes, its current content is saved as a revision.
 *
 *   wp stackless blocks migrate --from=marketers-delight [--dry-run] [--post=<id>] [--post_type=<type>]
 *   wp stackless blocks migrate --to=marketers-delight   [--dry-run] [--post=<id>] [--post_type=<type>]
 *
 * The same action is on Appearance > Stackless Tools.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/** The other theme's block namespace. */
const STACKLESS_THEME_BLOCKS_MD_NAMESPACE = 'marketers-delight';

/**
 * Rewrites the block delimiters in one piece of content.
 *
 * @param string $content   Post content.
 * @param string $direction "from" (Marketers Delight to Stackless) or "to" (back).
 * @return array{content: string, converted: int, skipped: array<string, int>}
 */
function stackless_theme_blocks_migrate_content(string $content, string $direction = 'from'): array
{
    $source = $direction === 'to' ? 'stackless' : STACKLESS_THEME_BLOCKS_MD_NAMESPACE;
    $target = $direction === 'to' ? STACKLESS_THEME_BLOCKS_MD_NAMESPACE : 'stackless';
    $known = array_keys(stackless_theme_blocks());
    $converted = 0;
    $skipped = [];

    if (strpos($content, 'wp:' . $source . '/') === false) {
        return ['content' => $content, 'converted' => 0, 'skipped' => []];
    }

    // A delimiter is "<!-- wp:ns/name", "<!-- /wp:ns/name" or the self-closing form; the name
    // ends at whitespace. Matching the comment opener keeps prose and attributes out of it.
    $content = (string) preg_replace_callback(
        '#(<!--\s+)(/?)wp:' . preg_quote($source, '#') . '/([a-z][a-z0-9-]*)(?=\s)#',
        static function (array $match) use ($known, $target, &$converted, &$skipped): string {
            if (!in_array($match[3], $known, true)) {
                if ($match[2] === '') {
                    $skipped[$match[3]] = ($skipped[$match[3]] ?? 0) + 1;
                }
                return $match[0];
            }
            if ($match[2] === '') {
                $converted++;
            }
            return $match[1] . $match[2] . 'wp:' . $target . '/' . $match[3];
        },
        $content
    );

    return ['content' => $content, 'converted' => $converted, 'skipped' => $skipped];
}

/**
 * Runs a migration over the site's posts.
 *
 * @param string $direction "from" or "to".
 * @param array  $args      dry_run (bool), post (int), post_type (string).
 * @return array{posts: int, blocks: int, skipped: array<string, int>, items: array<int, array<string, mixed>>, dry_run: bool, direction: string}
 */
function stackless_theme_blocks_migrate(string $direction = 'from', array $args = []): array
{
    global $wpdb;

    $direction = $direction === 'to' ? 'to' : 'from';
    $dry_run = !empty($args['dry_run']);
    $source = $direction === 'to' ? 'stackless' : STACKLESS_THEME_BLOCKS_MD_NAMESPACE;

    $where = $wpdb->prepare("post_content LIKE %s AND post_type <> 'revision' AND post_status NOT IN ('auto-draft', 'trash')", '%' . $wpdb->esc_like('wp:' . $source . '/') . '%');
    if (!empty($args['post'])) {
        $where .= $wpdb->prepare(' AND ID = %d', (int) $args['post']);
    }
    if (!empty($args['post_type'])) {
        $where .= $wpdb->prepare(' AND post_type = %s', sanitize_key($args['post_type']));
    }
    $ids = array_map('intval', (array) $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE {$where} ORDER BY ID")); // phpcs:ignore WordPress.DB -- prepared above.

    $result = ['posts' => 0, 'blocks' => 0, 'skipped' => [], 'items' => [], 'dry_run' => $dry_run, 'direction' => $direction];

    foreach ($ids as $id) {
        $post = get_post($id);
        if (!$post) {
            continue;
        }
        $change = stackless_theme_blocks_migrate_content((string) $post->post_content, $direction);
        foreach ($change['skipped'] as $name => $count) {
            $result['skipped'][$name] = ($result['skipped'][$name] ?? 0) + $count;
        }
        if ($change['converted'] === 0 || $change['content'] === $post->post_content) {
            continue;
        }

        $revision = 0;
        if (!$dry_run) {
            $revision = stackless_theme_blocks_migrate_backup($post);
            // Written straight to the table: wp_update_post() would run the content through the
            // save filters (kses for a user without unfiltered_html, as under WP-CLI) and change
            // the modified date. Only the delimiters change here.
            $wpdb->update($wpdb->posts, ['post_content' => $change['content']], ['ID' => $id]); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            clean_post_cache($id);
        }

        $result['posts']++;
        $result['blocks'] += $change['converted'];
        $result['items'][] = [
            'id' => $id,
            'title' => get_the_title($post) !== '' ? get_the_title($post) : '#' . $id,
            'type' => $post->post_type,
            'blocks' => $change['converted'],
            'revision' => $revision,
        ];
    }

    ksort($result['skipped']);
    return $result;
}

/**
 * Saves the post's current content as a revision and returns the revision's ID. Reuses the
 * latest revision when it already holds the same content.
 */
function stackless_theme_blocks_migrate_backup(WP_Post $post): int
{
    $latest = wp_get_post_revisions($post->ID, ['posts_per_page' => 1, 'check_enabled' => false]);
    $latest = $latest ? reset($latest) : null;
    if ($latest && $latest->post_content === $post->post_content) {
        return (int) $latest->ID;
    }
    $revision = function_exists('_wp_put_post_revision') ? _wp_put_post_revision($post->ID) : 0;
    return is_wp_error($revision) ? 0 : (int) $revision;
}

/* ------------------------------------------------------------------ WP-CLI */

if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    /**
     * Stackless block tools.
     */
    class Stackless_Theme_Blocks_CLI
    {
        /**
         * Converts blocks between Marketers Delight and Stackless in post content.
         *
         * Only the block comment delimiters change. Each changed post gets a revision of its
         * content first.
         *
         * ## OPTIONS
         *
         * [--from=<namespace>]
         * : Convert this theme's blocks to Stackless blocks. Accepts: marketers-delight.
         *
         * [--to=<namespace>]
         * : Convert Stackless blocks to this theme's blocks. Accepts: marketers-delight.
         *
         * [--dry-run]
         * : List what would change without writing anything.
         *
         * [--post=<id>]
         * : Convert one post only.
         *
         * [--post_type=<type>]
         * : Convert one post type only.
         *
         * ## EXAMPLES
         *
         *     wp stackless blocks migrate --from=marketers-delight --dry-run
         *     wp stackless blocks migrate --from=marketers-delight
         *     wp stackless blocks migrate --to=marketers-delight
         *
         * @param array $args       Positional arguments.
         * @param array $assoc_args Named arguments.
         */
        public function migrate($args, $assoc_args)
        {
            $from = (string) ($assoc_args['from'] ?? '');
            $to = (string) ($assoc_args['to'] ?? '');
            if (($from === '') === ($to === '')) {
                WP_CLI::error('Pass either --from=marketers-delight or --to=marketers-delight.');
            }
            if (($from !== '' ? $from : $to) !== STACKLESS_THEME_BLOCKS_MD_NAMESPACE) {
                WP_CLI::error('The only supported namespace is marketers-delight.');
            }

            $result = stackless_theme_blocks_migrate($from !== '' ? 'from' : 'to', [
                'dry_run' => !empty($assoc_args['dry-run']),
                'post' => (int) ($assoc_args['post'] ?? 0),
                'post_type' => (string) ($assoc_args['post_type'] ?? ''),
            ]);

            if ($result['items']) {
                WP_CLI\Utils\format_items('table', $result['items'], ['id', 'type', 'title', 'blocks', 'revision']);
            }
            foreach ($result['skipped'] as $name => $count) {
                WP_CLI::warning(sprintf('Left as it is: %s (%d). Stackless has no such block.', $name, $count));
            }
            $summary = sprintf('%d blocks in %d posts.', $result['blocks'], $result['posts']);
            if ($result['dry_run']) {
                WP_CLI::log('Dry run: would convert ' . $summary);
            } else {
                WP_CLI::success('Converted ' . $summary);
            }
        }
    }
    WP_CLI::add_command('stackless blocks', 'Stackless_Theme_Blocks_CLI');
}

/* ------------------------------------------------------------------ Tools screen */

add_action('admin_post_stackless_blocks_migrate', 'stackless_theme_blocks_migrate_action');
function stackless_theme_blocks_migrate_action(): void
{
    if (!function_exists('stackless_theme_tools_guard')) {
        wp_die(esc_html__('The Stackless tools are not available.', 'stackless-theme'));
    }
    stackless_theme_tools_guard('stackless_blocks_migrate');
    $direction = isset($_POST['direction']) && sanitize_key(wp_unslash($_POST['direction'])) === 'to' ? 'to' : 'from';
    $result = stackless_theme_blocks_migrate($direction, ['dry_run' => empty($_POST['convert'])]);
    set_transient('stackless_blocks_migrate_' . get_current_user_id(), $result, 10 * MINUTE_IN_SECONDS);
    stackless_theme_tools_redirect($result['posts'] === 0 ? 'blocks-none' : ($result['dry_run'] ? 'blocks-preview' : 'blocks-done'));
}

/** The Blocks card on Appearance > Stackless Tools. */
function stackless_theme_blocks_tools_card(): void
{
    $notice = isset($_GET['notice']) ? sanitize_key(wp_unslash($_GET['notice'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
    $result = strpos($notice, 'blocks-') === 0 ? get_transient('stackless_blocks_migrate_' . get_current_user_id()) : false;
    $labels = [
        'from' => __('Marketers Delight to Stackless', 'stackless-theme'),
        'to' => __('Stackless to Marketers Delight', 'stackless-theme'),
    ];
    ?>
    <div class="card" id="stackless-blocks-migrate">
        <h2><?php esc_html_e('Convert blocks', 'stackless-theme'); ?></h2>
        <p><?php esc_html_e('Stackless includes the Marketers Delight content blocks under its own name. Converting rewrites the block names in your posts, pages and patterns, nothing else, and saves a revision of each one first. Preview lists what would change without changing it.', 'stackless-theme'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="stackless_blocks_migrate">
            <?php wp_nonce_field('stackless_blocks_migrate'); ?>
            <p>
                <select name="direction">
                    <?php foreach ($labels as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>"<?php selected(is_array($result) ? $result['direction'] : 'from', $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <?php submit_button(__('Preview', 'stackless-theme'), 'secondary', 'preview', false); ?>
            <?php submit_button(__('Convert', 'stackless-theme'), 'primary', 'convert', false, ['onclick' => "return confirm('" . esc_js(__('Convert the blocks now? Each changed post keeps a revision of its current content.', 'stackless-theme')) . "');"]); ?>
        </form>
        <?php if (is_array($result)) : ?>
            <h3><?php echo esc_html($labels[$result['direction']] . ': ' . ($result['dry_run'] ? __('preview', 'stackless-theme') : __('converted', 'stackless-theme'))); ?></h3>
            <?php if ($result['items']) : ?>
                <table class="widefat striped">
                    <thead><tr><th><?php esc_html_e('Post', 'stackless-theme'); ?></th><th><?php esc_html_e('Type', 'stackless-theme'); ?></th><th><?php esc_html_e('Blocks', 'stackless-theme'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($result['items'], 0, 100) as $item) : ?>
                        <tr>
                            <td><a href="<?php echo esc_url((string) get_edit_post_link((int) $item['id'])); ?>"><?php echo esc_html((string) $item['title']); ?></a></td>
                            <td><?php echo esc_html((string) $item['type']); ?></td>
                            <td><?php echo esc_html((string) $item['blocks']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($result['items']) > 100) : ?>
                    <?php /* translators: %d: number of posts not listed */ ?>
                    <p class="description"><?php echo esc_html(sprintf(__('And %d more.', 'stackless-theme'), count($result['items']) - 100)); ?></p>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($result['skipped']) : ?>
                <?php /* translators: %s: comma-separated list of block names */ ?>
                <p class="description"><?php echo esc_html(sprintf(__('Left as they are, because Stackless has no such block: %s.', 'stackless-theme'), implode(', ', array_keys($result['skipped'])))); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
