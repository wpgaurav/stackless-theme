<?php
/**
 * Card blocks: Plugin, Theme, GitHub, npm, Book, YouTube and Podcast.
 *
 * Each card saves what the editor fetched into its attributes and renders from them, so a page
 * view never depends on an outside service. Three things here do talk to one:
 *
 * - The Plugin, Theme, GitHub and npm cards overlay their saved stats (rating, installs, stars,
 *   version) with values no older than a day. A page view never waits for them: on a cache miss
 *   the card renders its saved values and a one-off cron event fetches fresh ones.
 * - The Plugin and Theme cards copy their icon, banner and screenshot from WordPress.org into the
 *   media library when the editor fetches them.
 * - The Podcast card reads its feed through the server, since a browser cannot read most feeds.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/* ------------------------------------------------------------------ Helpers */

/** The card's variant: cardStyle, or the legacy string "style", as a class-safe name. */
function stackless_theme_blocks_card_style(array $attributes): string
{
    $style = $attributes['cardStyle'] ?? (isset($attributes['style']) && is_string($attributes['style']) ? $attributes['style'] : 'card');
    $style = sanitize_html_class((string) $style);
    return $style !== '' ? $style : 'card';
}

/** The class attribute value of a card's root element. */
function stackless_theme_blocks_card_classes(string $card, array $attributes): string
{
    $classes = ['stb-' . $card . '-card', 'stb-' . $card . '-card-' . stackless_theme_blocks_card_style($attributes)];
    return esc_attr(implode(' ', array_merge($classes, stackless_theme_blocks_classes($attributes['marginBottom'] ?? 'mb-double'))));
}

/** A text attribute with its HTML entities decoded, as the editor may have saved them encoded. */
function stackless_theme_blocks_card_text(array $attributes, string $field): string
{
    return isset($attributes[$field]) && is_scalar($attributes[$field]) ? html_entity_decode((string) $attributes[$field], ENT_QUOTES, 'UTF-8') : '';
}

/** A link or image URL attribute, or '' when it is unset or not an http(s) URL. */
function stackless_theme_blocks_card_url(array $attributes, string $field): string
{
    return isset($attributes[$field]) && is_string($attributes[$field]) ? esc_url_raw(trim($attributes[$field]), ['http', 'https']) : '';
}

/** A list attribute (topics, keywords, authors, subjects) as clean strings. */
function stackless_theme_blocks_card_list(array $attributes, string $field): array
{
    $list = [];
    foreach ((array) ($attributes[$field] ?? []) as $item) {
        if (is_scalar($item) && trim((string) $item) !== '') {
            $list[] = (string) $item;
        }
    }
    return $list;
}

/**
 * One of the cards' small inline icons: the matching Tabler icon as an <svg> carrying the
 * card's icon class.
 */
function stackless_theme_blocks_card_icon(string $name, int $size = 14, string $class = 'stb-card-icon'): string
{
    $svg = stackless_theme_icon($name, $size);
    return $svg === '' || $class === '' ? $svg : str_replace('class="st-icon ', 'class="' . esc_attr($class) . ' st-icon ', $svg);
}

/** The card icons the editor previews draw, keyed by the name the editor scripts use. */
function stackless_theme_blocks_card_icons(): array
{
    return [
        'tag' => stackless_theme_icon('tag', 14),
        'fork' => stackless_theme_icon('git-fork', 14),
        'scale' => stackless_theme_icon('scale', 14),
        'user' => stackless_theme_icon('user', 14),
        'calendar' => stackless_theme_icon('calendar', 14),
        'package' => stackless_theme_icon('package', 20),
    ];
}

/** A JSON-LD script tag, or '' for empty data or when the filter turns the card's schema off. */
function stackless_theme_blocks_card_schema(array $data, string $slug): string
{
    /**
     * Filters a card block's structured data. Return an empty array to print none.
     *
     * @param array  $data Schema.org data.
     * @param string $slug Block name after the slash, e.g. "plugin-card".
     */
    $data = (array) apply_filters('stackless_theme_blocks_card_schema', $data, $slug);
    if (!$data) {
        return '';
    }
    return '<script type="application/ld+json">' . wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

/**
 * SoftwareApplication data for a Plugin or Theme card. Rich results need a real offer and real
 * aggregated ratings, so this returns an empty array without both.
 */
function stackless_theme_blocks_card_software(array $a, string $kind): array
{
    if (isset($a['schemaEnabled']) && !$a['schemaEnabled']) {
        return [];
    }
    $name = trim(wp_strip_all_tags(stackless_theme_blocks_card_text($a, 'name')));
    $rating = stackless_theme_blocks_card_stars($a['rating'] ?? 0);
    $count = (int) ($a['ratingCount'] ?? 0);
    if ($name === '' || $rating < 1 || $count < 1) {
        return [];
    }
    $url = esc_url_raw((string) ($a['wpUrl'] ?? ''), ['https', 'http']);
    $price = (string) ($a['price'] ?? '');
    // A WordPress.org directory listing describes the free version.
    if ($price === '' && wp_parse_url($url, PHP_URL_HOST) === 'wordpress.org'
        && strpos((string) wp_parse_url($url, PHP_URL_PATH), '/' . ($kind === 'plugin' ? 'plugins' : 'themes') . '/') === 0) {
        $price = '0';
    }
    $currency = strtoupper((string) ($a['priceCurrency'] ?? 'USD'));
    if ($price === '' || !is_numeric($price) || (float) $price < 0 || !preg_match('/^[A-Z]{3}$/', $currency)) {
        return [];
    }
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => $name,
        'applicationCategory' => $kind === 'plugin' ? 'DeveloperApplication' : 'DesignApplication',
        'operatingSystem' => 'WordPress',
        'offers' => ['@type' => 'Offer', 'price' => $price, 'priceCurrency' => $currency],
        'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => $rating, 'ratingCount' => $count, 'bestRating' => 5, 'worstRating' => 1],
    ];
    if ($url !== '') {
        $data['url'] = $url;
        $data['@id'] = $url . '#software';
    }
    $description = wp_strip_all_tags(stackless_theme_blocks_card_text($a, 'description'));
    if ($description !== '') {
        $data['description'] = $description;
    }
    $author = wp_strip_all_tags(stackless_theme_blocks_card_text($a, 'author'));
    if ($author !== '' && in_array($a['authorType'] ?? '', ['Person', 'Organization'], true)) {
        $data['author'] = ['@type' => $a['authorType'], 'name' => $author];
    }
    return $data;
}

/**
 * A rating on the 0 to 5 scale. WordPress.org reports 0 to 100; a value saved unconverted would
 * otherwise print one star per point.
 */
function stackless_theme_blocks_card_stars($rating): float
{
    $rating = (float) $rating;
    if ($rating > 5) {
        $rating = round($rating / 20 * 2) / 2;
    }
    return max(0.0, min(5.0, $rating));
}

/** The star icons for a rating, as empty stb-icon elements the render wrapper fills. */
function stackless_theme_blocks_card_star_icons(float $rating): string
{
    if ($rating <= 0) {
        return '';
    }
    $full = (int) floor($rating);
    $stars = str_repeat('<span class="stb-icon-star-full text-warning"></span>', $full);
    if ($rating - $full >= 0.5) {
        $stars .= '<span class="stb-icon-star-half text-warning"></span>';
    }
    return $stars;
}

/** The price label of a Plugin or Theme card that carries an offer: "Free" or "49 USD". */
function stackless_theme_blocks_card_price(array $schema): string
{
    if (empty($schema['offers'])) {
        return '';
    }
    $label = (float) $schema['offers']['price'] === 0.0 ? __('Free', 'stackless-theme') : $schema['offers']['price'] . ' ' . $schema['offers']['priceCurrency'];
    return '<span class="stb-software-price">' . esc_html($label) . '</span>';
}

/**
 * The dot color for a GitHub card's language: the saved languageColor when it is a hex color,
 * else GitHub's color for the language or a neutral gray, as the editor preview draws it.
 */
function stackless_theme_blocks_card_language_color(string $language, string $saved = ''): string
{
    $saved = (string) sanitize_hex_color($saved);
    if ($saved !== '') {
        return $saved;
    }
    $colors = [
        'JavaScript' => '#f1e05a', 'TypeScript' => '#3178c6', 'Python' => '#3572A5', 'Java' => '#b07219',
        'Go' => '#00ADD8', 'Rust' => '#dea584', 'Ruby' => '#701516', 'PHP' => '#4F5D95', 'C#' => '#178600',
        'C++' => '#f34b7d', 'C' => '#555555', 'Swift' => '#F05138', 'Kotlin' => '#A97BFF', 'Dart' => '#00B4AB',
        'Shell' => '#89e051', 'HTML' => '#e34c26', 'CSS' => '#563d7c', 'Vue' => '#41b883', 'Svelte' => '#ff3e00',
        'Lua' => '#000080', 'Elixir' => '#6e4a7e', 'Scala' => '#c22d40', 'R' => '#198CE7', 'Haskell' => '#5e5086',
        'Zig' => '#ec915c',
    ];
    return $colors[$language] ?? '#8b8b8b';
}

/* ------------------------------------------------------------------ Render */

function stackless_theme_blocks_render_plugin_card(array $attributes, string $content = ''): string
{
    $attributes = stackless_theme_blocks_card_merge('plugin', $attributes);
    $name = stackless_theme_blocks_card_text($attributes, 'name');
    if ($name === '') {
        return '';
    }
    $description = stackless_theme_blocks_card_text($attributes, 'description');
    $author = stackless_theme_blocks_card_text($attributes, 'author');
    $icon = stackless_theme_blocks_card_url($attributes, 'icon');
    $banner = stackless_theme_blocks_card_url($attributes, 'banner');
    $rating = stackless_theme_blocks_card_stars($attributes['rating'] ?? 0);
    $rating_count = (int) ($attributes['ratingCount'] ?? 0);
    $installs = (string) ($attributes['activeInstalls'] ?? '');
    $version = (string) ($attributes['version'] ?? '');
    $tested = (string) ($attributes['testedUpTo'] ?? '');
    $requires_wp = (string) ($attributes['requiresWP'] ?? '');
    $requires_php = (string) ($attributes['requiresPHP'] ?? '');
    $download_url = stackless_theme_blocks_card_url($attributes, 'downloadUrl');
    $wp_url = stackless_theme_blocks_card_url($attributes, 'wpUrl');
    $show_requirements = !empty($attributes['showRequirements']) && ($tested !== '' || $requires_wp !== '' || $requires_php !== '');
    $show_banner = ($attributes['showBanner'] ?? true) && $banner !== ''
        && in_array(stackless_theme_blocks_card_style($attributes), ['card', 'horizontal'], true);
    $schema = stackless_theme_blocks_card_software($attributes, 'plugin');
    $stars = stackless_theme_blocks_card_star_icons($rating);

    ob_start();
    ?>
    <div class="<?php echo stackless_theme_blocks_card_classes('plugin', $attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>">
        <?php echo stackless_theme_blocks_card_schema($schema, 'plugin-card'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded in the helper. ?>
        <?php if ($show_banner) : ?>
            <div class="stb-plugin-card-banner">
                <?php echo stackless_theme_blocks_image($banner, 'large', ['alt' => $name, 'loading' => 'lazy']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>
        <div class="stb-plugin-card-body">
            <div class="stb-plugin-card-header">
                <?php if ($icon !== '') : ?>
                    <?php echo stackless_theme_blocks_image($icon, 'large', ['alt' => $name, 'class' => 'stb-plugin-card-icon', 'loading' => 'lazy']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php else : ?>
                    <span class="stb-plugin-card-icon-placeholder stb-icon-plugin"></span>
                <?php endif; ?>
                <div class="stb-plugin-card-info">
                    <h3 class="stb-plugin-card-name mb-none"><?php echo esc_html($name); ?></h3>
                    <?php if ($author !== '') : ?>
                        <?php /* translators: %s: the plugin's author. */ ?>
                        <span class="stb-plugin-card-author text-sec small"><?php echo esc_html(sprintf(__('by %s', 'stackless-theme'), $author)); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($description !== '') : ?>
                <p class="stb-plugin-card-desc"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
            <div class="stb-plugin-card-meta">
                <?php echo stackless_theme_blocks_card_price($schema); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>
                <?php if ($stars !== '') : ?>
                    <span class="stb-plugin-card-rating">
                        <?php echo $stars; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup. ?>
                        <?php if ($rating_count > 0) : ?>
                            <span class="stb-plugin-card-rating-count">(<?php echo esc_html(number_format_i18n($rating_count)); ?>)</span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                <?php if ($installs !== '') : ?>
                    <span class="stb-plugin-card-installs"><span class="stb-icon-download"></span> <?php echo esc_html($installs); ?>+</span>
                <?php endif; ?>
                <?php if ($version !== '') : ?>
                    <span class="stb-plugin-card-version"><?php echo stackless_theme_blocks_card_icon('tag'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG. ?> v<?php echo esc_html($version); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($show_requirements) : ?>
                <div class="stb-plugin-card-requirements">
                    <?php if ($tested !== '') : ?>
                        <span><?php echo esc_html(__('Tested up to:', 'stackless-theme') . ' ' . $tested); ?></span>
                    <?php endif; ?>
                    <?php if ($requires_wp !== '') : ?>
                        <span><?php echo esc_html(__('Requires WP:', 'stackless-theme') . ' ' . $requires_wp); ?></span>
                    <?php endif; ?>
                    <?php if ($requires_php !== '') : ?>
                        <span><?php echo esc_html(__('Requires PHP:', 'stackless-theme') . ' ' . $requires_php); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="stb-plugin-card-actions">
                <?php if ($wp_url !== '') : ?>
                    <a href="<?php echo esc_url($wp_url); ?>" class="button" target="_blank" rel="noopener"><?php esc_html_e('View on WordPress.org', 'stackless-theme'); ?></a>
                <?php endif; ?>
                <?php if ($download_url !== '') : ?>
                    <a href="<?php echo esc_url($download_url); ?>" class="button button-outline"><?php esc_html_e('Download', 'stackless-theme'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_theme_card(array $attributes, string $content = ''): string
{
    $attributes = stackless_theme_blocks_card_merge('theme', $attributes);
    $name = stackless_theme_blocks_card_text($attributes, 'name');
    if ($name === '') {
        return '';
    }
    $description = stackless_theme_blocks_card_text($attributes, 'description');
    $author = stackless_theme_blocks_card_text($attributes, 'author');
    $screenshot = stackless_theme_blocks_card_url($attributes, 'screenshot');
    $rating = stackless_theme_blocks_card_stars($attributes['rating'] ?? 0);
    $rating_count = (int) ($attributes['ratingCount'] ?? 0);
    $installs = (string) ($attributes['activeInstalls'] ?? '');
    $version = (string) ($attributes['version'] ?? '');
    $requires_wp = (string) ($attributes['requiresWP'] ?? '');
    $requires_php = (string) ($attributes['requiresPHP'] ?? '');
    $preview_url = stackless_theme_blocks_card_url($attributes, 'previewUrl');
    $wp_url = stackless_theme_blocks_card_url($attributes, 'wpUrl');
    $show_requirements = !empty($attributes['showRequirements']) && ($requires_wp !== '' || $requires_php !== '');
    $schema = stackless_theme_blocks_card_software($attributes, 'theme');
    $stars = stackless_theme_blocks_card_star_icons($rating);

    ob_start();
    ?>
    <div class="<?php echo stackless_theme_blocks_card_classes('theme', $attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>">
        <?php echo stackless_theme_blocks_card_schema($schema, 'theme-card'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded in the helper. ?>
        <?php if ($screenshot !== '') : ?>
            <div class="stb-theme-card-screenshot">
                <?php echo stackless_theme_blocks_image($screenshot, 'large', ['alt' => $name, 'loading' => 'lazy']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php if ($preview_url !== '') : ?>
                    <a href="<?php echo esc_url($preview_url); ?>" class="stb-theme-card-preview-overlay" target="_blank" rel="noopener">
                        <span class="stb-icon-eye"></span> <?php esc_html_e('Preview', 'stackless-theme'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="stb-theme-card-content">
            <h3 class="stb-theme-card-name mb-none"><?php echo esc_html($name); ?></h3>
            <?php if ($author !== '') : ?>
                <?php /* translators: %s: the theme's author. */ ?>
                <span class="stb-theme-card-author text-sec small"><?php echo esc_html(sprintf(__('by %s', 'stackless-theme'), $author)); ?></span>
            <?php endif; ?>
            <?php if ($description !== '') : ?>
                <p class="stb-theme-card-desc"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
            <div class="stb-theme-card-meta">
                <?php echo stackless_theme_blocks_card_price($schema); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>
                <?php if ($stars !== '') : ?>
                    <span class="stb-theme-card-rating">
                        <?php echo $stars; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup. ?>
                        <?php if ($rating_count > 0) : ?>
                            <span class="stb-theme-card-rating-count">(<?php echo esc_html(number_format_i18n($rating_count)); ?>)</span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                <?php if ($installs !== '') : ?>
                    <span class="stb-theme-card-installs"><span class="stb-icon-download"></span> <?php echo esc_html($installs); ?>+</span>
                <?php endif; ?>
                <?php if ($version !== '') : ?>
                    <span class="stb-theme-card-version"><?php echo stackless_theme_blocks_card_icon('tag'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG. ?> v<?php echo esc_html($version); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($show_requirements) : ?>
                <div class="stb-theme-card-requirements">
                    <?php if ($requires_wp !== '') : ?>
                        <span><?php echo esc_html(__('Requires WP:', 'stackless-theme') . ' ' . $requires_wp); ?></span>
                    <?php endif; ?>
                    <?php if ($requires_php !== '') : ?>
                        <span><?php echo esc_html(__('Requires PHP:', 'stackless-theme') . ' ' . $requires_php); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="stb-theme-card-actions">
                <?php if ($wp_url !== '') : ?>
                    <a href="<?php echo esc_url($wp_url); ?>" class="button" target="_blank" rel="noopener"><?php esc_html_e('View on WordPress.org', 'stackless-theme'); ?></a>
                <?php endif; ?>
                <?php if ($preview_url !== '') : ?>
                    <a href="<?php echo esc_url($preview_url); ?>" class="button button-outline" target="_blank" rel="noopener"><?php esc_html_e('Live Preview', 'stackless-theme'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_github_card(array $attributes, string $content = ''): string
{
    $attributes = stackless_theme_blocks_card_merge('github', $attributes);
    $name = stackless_theme_blocks_card_text($attributes, 'name');
    if ($name === '') {
        return '';
    }
    $repo_slug = (string) ($attributes['repoSlug'] ?? '');
    $description = stackless_theme_blocks_card_text($attributes, 'description');
    $language = (string) ($attributes['language'] ?? '');
    $language_color = stackless_theme_blocks_card_language_color($language, (string) ($attributes['languageColor'] ?? ''));
    $stars = (int) ($attributes['stars'] ?? 0);
    $forks = (int) ($attributes['forks'] ?? 0);
    // GitHub reports NOASSERTION for a license it cannot classify. The editor preview hides it too.
    $license = (string) ($attributes['license'] ?? '');
    $license = $license === 'NOASSERTION' ? '' : $license;
    $owner = (string) ($attributes['owner'] ?? '');
    $avatar = stackless_theme_blocks_card_url($attributes, 'ownerAvatar');
    $updated = !empty($attributes['lastUpdated']) ? strtotime((string) $attributes['lastUpdated']) : false;
    $repo_url = stackless_theme_blocks_card_url($attributes, 'repoUrl');
    $topics = stackless_theme_blocks_card_list($attributes, 'topics');
    $url = $repo_url !== '' ? $repo_url : ($repo_slug !== '' ? 'https://github.com/' . $repo_slug : '');

    $schema = ['@context' => 'https://schema.org', '@type' => 'SoftwareSourceCode', 'name' => $name, 'codeRepository' => $url];
    if ($description !== '') {
        $schema['description'] = $description;
    }
    if ($language !== '') {
        $schema['programmingLanguage'] = $language;
    }
    if ($license !== '') {
        $schema['license'] = $license;
    }
    if ($owner !== '') {
        $schema['author'] = ['@type' => 'Person', 'name' => $owner];
    }

    ob_start();
    ?>
    <div class="<?php echo stackless_theme_blocks_card_classes('github', $attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>">
        <?php echo stackless_theme_blocks_card_schema($schema, 'github-card'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded in the helper. ?>
        <div class="stb-github-card-header">
            <?php if ($avatar !== '') : ?>
                <?php echo stackless_theme_blocks_image($avatar, 'large', ['alt' => $owner, 'class' => 'stb-github-card-avatar', 'loading' => 'lazy']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
            <div class="stb-github-card-identity">
                <h3 class="stb-github-card-name mb-none"><?php echo esc_html($name); ?></h3>
                <?php if ($owner !== '') : ?>
                    <span class="stb-github-card-owner text-sec small"><?php echo esc_html($owner); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($description !== '') : ?>
            <p class="stb-github-card-desc"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <div class="stb-github-card-meta">
            <?php if ($stars > 0) : ?>
                <span class="stb-github-card-stat stb-github-card-stars"><span class="stb-icon-star-full"></span> <?php echo esc_html(number_format_i18n($stars)); ?></span>
            <?php endif; ?>
            <?php if ($forks > 0) : ?>
                <span class="stb-github-card-stat stb-github-card-forks"><?php echo stackless_theme_blocks_card_icon('git-fork', 14, 'stb-github-card-icon'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG. ?> <?php echo esc_html(number_format_i18n($forks)); ?></span>
            <?php endif; ?>
            <?php if ($language !== '') : ?>
                <span class="stb-github-card-language">
                    <span class="stb-github-card-language-dot"<?php echo $language_color ? ' style="background-color: ' . esc_attr($language_color) . '"' : ''; ?>></span>
                    <?php echo esc_html($language); ?>
                </span>
            <?php endif; ?>
            <?php if ($license !== '') : ?>
                <span class="stb-github-card-stat stb-github-card-license"><span class="stb-icon-shield-check"></span> <?php echo esc_html($license); ?></span>
            <?php endif; ?>
            <?php if ($updated) : ?>
                <?php /* translators: %s: a time span such as "3 days". */ ?>
                <span class="stb-github-card-stat"><span class="stb-icon-clock"></span> <?php echo esc_html(sprintf(__('%s ago', 'stackless-theme'), human_time_diff($updated))); ?></span>
            <?php endif; ?>
        </div>
        <?php if ($topics) : ?>
            <div class="stb-github-card-topics">
                <?php foreach ($topics as $topic) : ?>
                    <span class="stb-github-card-topic"><?php echo esc_html($topic); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="stb-github-card-actions">
            <?php if ($url !== '') : ?>
                <a href="<?php echo esc_url($url); ?>" class="button" target="_blank" rel="noopener"><?php esc_html_e('View on GitHub', 'stackless-theme'); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_npm_card(array $attributes, string $content = ''): string
{
    $attributes = stackless_theme_blocks_card_merge('npm', $attributes);
    $package = (string) ($attributes['packageName'] ?? '');
    if ($package === '') {
        return '';
    }
    $description = stackless_theme_blocks_card_text($attributes, 'description');
    $author = stackless_theme_blocks_card_text($attributes, 'author');
    $version = (string) ($attributes['version'] ?? '');
    $license = (string) ($attributes['license'] ?? '');
    $downloads = (string) ($attributes['weeklyDownloads'] ?? '');
    $homepage = stackless_theme_blocks_card_url($attributes, 'homepage');
    $npm_url = stackless_theme_blocks_card_url($attributes, 'npmUrl');
    $published = (string) ($attributes['lastPublished'] ?? '');
    $keywords = stackless_theme_blocks_card_list($attributes, 'keywords');

    $schema = ['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => $package, 'applicationCategory' => 'npm Package'];
    if ($description !== '') {
        $schema['description'] = $description;
    }
    if ($version !== '') {
        $schema['softwareVersion'] = $version;
    }
    if ($npm_url !== '') {
        $schema['url'] = $npm_url;
    }
    if ($author !== '') {
        $schema['author'] = ['@type' => 'Person', 'name' => $author];
    }
    if ($license !== '') {
        $schema['license'] = $license;
    }

    ob_start();
    ?>
    <div class="<?php echo stackless_theme_blocks_card_classes('npm', $attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>">
        <?php echo stackless_theme_blocks_card_schema($schema, 'npm-card'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded in the helper. ?>
        <div class="stb-npm-card-header">
            <span class="stb-npm-card-icon"><?php echo stackless_theme_blocks_card_icon('package', 20, ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG. ?></span>
            <div class="stb-npm-card-identity">
                <h3 class="stb-npm-card-name mb-none"><code><?php echo esc_html($package); ?></code></h3>
                <?php if ($author !== '') : ?>
                    <span class="stb-npm-card-author text-sec small"><?php echo esc_html($author); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($description !== '') : ?>
            <p class="stb-npm-card-desc"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <div class="stb-npm-card-meta">
            <?php if ($version !== '') : ?>
                <span class="stb-npm-card-version"><?php echo stackless_theme_blocks_card_icon('tag'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG. ?> v<?php echo esc_html($version); ?></span>
            <?php endif; ?>
            <?php if ($downloads !== '') : ?>
                <?php /* translators: %s: a download count such as "1.2M". */ ?>
                <span class="stb-npm-card-downloads"><span class="stb-icon-download"></span> <?php echo esc_html(sprintf(__('%s/wk', 'stackless-theme'), $downloads)); ?></span>
            <?php endif; ?>
            <?php if ($license !== '') : ?>
                <span class="stb-npm-card-license"><?php echo stackless_theme_blocks_card_icon('scale'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG. ?> <?php echo esc_html($license); ?></span>
            <?php endif; ?>
            <?php if ($published !== '') : ?>
                <span class="stb-npm-card-published"><span class="stb-icon-clock"></span> <?php echo esc_html($published); ?></span>
            <?php endif; ?>
        </div>
        <?php if ($keywords) : ?>
            <div class="stb-npm-card-keywords">
                <?php foreach ($keywords as $keyword) : ?>
                    <span class="stb-npm-card-keyword"><?php echo esc_html($keyword); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="stb-npm-card-actions">
            <?php if ($npm_url !== '') : ?>
                <a href="<?php echo esc_url($npm_url); ?>" class="button" target="_blank" rel="noopener"><?php esc_html_e('View on npm', 'stackless-theme'); ?></a>
            <?php endif; ?>
            <?php if ($homepage !== '') : ?>
                <a href="<?php echo esc_url($homepage); ?>" class="button button-outline" target="_blank" rel="noopener"><?php esc_html_e('Homepage', 'stackless-theme'); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_book_card(array $attributes, string $content = ''): string
{
    $title = stackless_theme_blocks_card_text($attributes, 'title');
    if ($title === '') {
        return '';
    }
    $isbn = (string) ($attributes['isbn'] ?? '');
    $authors = stackless_theme_blocks_card_list($attributes, 'authors');
    $cover = stackless_theme_blocks_card_url($attributes, 'coverImage');
    $year = (string) ($attributes['publishYear'] ?? '');
    $pages = (int) ($attributes['pageCount'] ?? 0);
    $publisher = stackless_theme_blocks_card_text($attributes, 'publisher');
    $subjects = stackless_theme_blocks_card_list($attributes, 'subjects');
    $description = stackless_theme_blocks_card_text($attributes, 'description');
    $library_url = stackless_theme_blocks_card_url($attributes, 'openLibraryUrl');

    $schema = ['@context' => 'https://schema.org', '@type' => 'Book', 'name' => $title];
    if ($isbn !== '') {
        $schema['isbn'] = $isbn;
    }
    if ($description !== '') {
        $schema['description'] = $description;
    }
    if ($cover !== '') {
        $schema['image'] = $cover;
    }
    if ($pages > 0) {
        $schema['numberOfPages'] = $pages;
    }
    if ($publisher !== '') {
        $schema['publisher'] = ['@type' => 'Organization', 'name' => $publisher];
    }
    foreach ($authors as $author) {
        $schema['author'][] = ['@type' => 'Person', 'name' => $author];
    }
    if ($library_url !== '') {
        $schema['url'] = $library_url;
    }

    ob_start();
    ?>
    <div class="<?php echo stackless_theme_blocks_card_classes('book', $attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>">
        <?php echo stackless_theme_blocks_card_schema($schema, 'book-card'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded in the helper. ?>
        <div class="stb-book-card-layout">
            <?php if ($cover !== '') : ?>
                <div class="stb-book-card-cover">
                    <?php echo stackless_theme_blocks_image($cover, 'large', ['alt' => $title, 'loading' => 'lazy']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>
            <div class="stb-book-card-body">
                <h3 class="stb-book-card-title mb-none"><?php echo esc_html($title); ?></h3>
                <?php if ($authors) : ?>
                    <span class="stb-book-card-authors text-sec"><?php echo stackless_theme_blocks_card_icon('user'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG. ?> <?php echo esc_html(implode(', ', $authors)); ?></span>
                <?php endif; ?>
                <div class="stb-book-card-meta">
                    <?php if ($publisher !== '') : ?>
                        <span class="stb-book-card-publisher"><?php echo esc_html($publisher); ?></span>
                    <?php endif; ?>
                    <?php if ($year !== '') : ?>
                        <span class="stb-book-card-year"><?php echo stackless_theme_blocks_card_icon('calendar'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG. ?> <?php echo esc_html($year); ?></span>
                    <?php endif; ?>
                    <?php if ($pages > 0) : ?>
                        <?php /* translators: %s: a number of pages. */ ?>
                        <span class="stb-book-card-pages"><span class="stb-icon-file"></span> <?php echo esc_html(sprintf(_n('%s page', '%s pages', $pages, 'stackless-theme'), number_format_i18n($pages))); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($description !== '') : ?>
                    <p class="stb-book-card-desc"><?php echo esc_html($description); ?></p>
                <?php endif; ?>
                <?php if ($subjects) : ?>
                    <div class="stb-book-card-subjects">
                        <?php foreach ($subjects as $subject) : ?>
                            <span class="stb-book-card-subject"><?php echo esc_html($subject); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="stb-book-card-actions">
                    <?php if ($library_url !== '') : ?>
                        <a href="<?php echo esc_url($library_url); ?>" class="button" target="_blank" rel="noopener"><?php esc_html_e('View on Open Library', 'stackless-theme'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

/** A link card, not a watch page: it prints no VideoObject and loads no player. */
function stackless_theme_blocks_render_youtube_card(array $attributes, string $content = ''): string
{
    $title = stackless_theme_blocks_card_text($attributes, 'title');
    if ($title === '') {
        return '';
    }
    $video_url = stackless_theme_blocks_card_url($attributes, 'videoUrl');
    $video_id = (string) ($attributes['videoId'] ?? '');
    $channel = stackless_theme_blocks_card_text($attributes, 'channelName');
    $thumbnail = stackless_theme_blocks_card_url($attributes, 'thumbnailUrl');
    $watch_url = $video_url !== '' ? $video_url : ($video_id !== '' ? 'https://www.youtube.com/watch?v=' . rawurlencode($video_id) : '');
    $image = $thumbnail !== '' ? stackless_theme_blocks_image($thumbnail, 'large', ['alt' => $title, 'loading' => 'lazy']) . '<span class="stb-youtube-card-play" aria-hidden="true">&#9654;</span>' : '';

    ob_start();
    ?>
    <div class="<?php echo stackless_theme_blocks_card_classes('youtube', $attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>">
        <?php if ($image !== '' && $watch_url !== '') : ?>
            <a href="<?php echo esc_url($watch_url); ?>" class="stb-youtube-card-thumbnail" target="_blank" rel="noopener"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></a>
        <?php elseif ($image !== '') : ?>
            <div class="stb-youtube-card-thumbnail"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></div>
        <?php endif; ?>
        <div class="stb-youtube-card-body">
            <h3 class="stb-youtube-card-title mb-none"><?php echo esc_html($title); ?></h3>
            <?php if ($channel !== '') : ?>
                <span class="stb-youtube-card-channel text-sec small"><span class="stb-icon-youtube"></span> <?php echo esc_html($channel); ?></span>
            <?php endif; ?>
            <?php if ($watch_url !== '') : ?>
                <div class="stb-youtube-card-actions">
                    <a href="<?php echo esc_url($watch_url); ?>" class="button" target="_blank" rel="noopener"><?php esc_html_e('Watch on YouTube', 'stackless-theme'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_podcast_card(array $attributes, string $content = ''): string
{
    $episode = stackless_theme_blocks_card_text($attributes, 'episodeTitle');
    if ($episode === '') {
        return '';
    }
    $podcast = stackless_theme_blocks_card_text($attributes, 'podcastTitle');
    $description = stackless_theme_blocks_card_text($attributes, 'episodeDescription');
    $artwork = stackless_theme_blocks_card_url($attributes, 'artworkUrl');
    $duration = (string) ($attributes['duration'] ?? '');
    $date = (string) ($attributes['publishDate'] ?? '');
    $audio_url = stackless_theme_blocks_card_url($attributes, 'audioUrl');
    $episode_url = stackless_theme_blocks_card_url($attributes, 'episodeUrl');

    $schema = ['@context' => 'https://schema.org', '@type' => 'PodcastEpisode', 'name' => $episode];
    if ($episode_url !== '') {
        $schema['url'] = $episode_url;
    }
    if ($date !== '') {
        $schema['datePublished'] = $date;
    }
    if ($description !== '') {
        $schema['description'] = $description;
    }
    if ($audio_url !== '') {
        $schema['associatedMedia'] = ['@type' => 'MediaObject', 'contentUrl' => $audio_url];
    }
    if ($podcast !== '') {
        $schema['partOfSeries'] = ['@type' => 'PodcastSeries', 'name' => $podcast];
    }

    ob_start();
    ?>
    <div class="<?php echo stackless_theme_blocks_card_classes('podcast', $attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>">
        <?php echo stackless_theme_blocks_card_schema($schema, 'podcast-card'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded in the helper. ?>
        <div class="stb-podcast-card-layout">
            <?php if ($artwork !== '') : ?>
                <div class="stb-podcast-card-artwork">
                    <?php echo stackless_theme_blocks_image($artwork, 'large', ['alt' => $podcast, 'loading' => 'lazy']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>
            <div class="stb-podcast-card-body">
                <?php if ($podcast !== '') : ?>
                    <span class="stb-podcast-card-show small"><?php echo esc_html($podcast); ?></span>
                <?php endif; ?>
                <h3 class="stb-podcast-card-title mb-none"><?php echo esc_html($episode); ?></h3>
                <div class="stb-podcast-card-meta">
                    <?php if ($duration !== '') : ?>
                        <span class="stb-podcast-card-duration"><span class="stb-icon-clock"></span> <?php echo esc_html($duration); ?></span>
                    <?php endif; ?>
                    <?php if ($date !== '') : ?>
                        <span class="stb-podcast-card-date"><?php echo stackless_theme_blocks_card_icon('calendar'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG. ?> <?php echo esc_html($date); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($description !== '') : ?>
                    <p class="stb-podcast-card-desc"><?php echo esc_html($description); ?></p>
                <?php endif; ?>
                <div class="stb-podcast-card-actions">
                    <?php if ($audio_url !== '') : ?>
                        <a href="<?php echo esc_url($audio_url); ?>" class="button" target="_blank" rel="noopener"><?php esc_html_e('Listen', 'stackless-theme'); ?></a>
                    <?php endif; ?>
                    <?php if ($episode_url !== '') : ?>
                        <a href="<?php echo esc_url($episode_url); ?>" class="button button-outline" target="_blank" rel="noopener"><?php esc_html_e('View Episode', 'stackless-theme'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ Fresh stats */

add_action('stackless_theme_blocks_refresh_card', 'stackless_theme_blocks_card_refresh', 10, 2);

/** The block attribute that names each card type's source. */
function stackless_theme_blocks_card_key_attribute(string $type): string
{
    $keys = ['plugin' => 'slug', 'theme' => 'slug', 'github' => 'repoSlug', 'npm' => 'packageName'];
    return $keys[$type] ?? '';
}

/**
 * The block's attributes with today's stats laid over the saved ones. Only the fields that
 * change are refreshed; names, descriptions, images and links stay as the editor saved them.
 */
function stackless_theme_blocks_card_merge(string $type, array $attributes): array
{
    /**
     * Filters whether the card blocks refresh their stats when they render. Return false to
     * print the saved values only and make no outside requests.
     *
     * @param bool   $refresh
     * @param string $type    plugin, theme, github or npm.
     */
    if (!apply_filters('stackless_theme_blocks_refresh_cards', true, $type)) {
        return $attributes;
    }
    $key = trim((string) ($attributes[stackless_theme_blocks_card_key_attribute($type)] ?? ''));
    if (!stackless_theme_blocks_card_valid_key($type, $key)) {
        return $attributes;
    }
    // An empty fresh value never replaces a saved one: the themes API, for one, reports no
    // install count, and a count typed in by hand should stay.
    $fresh = array_filter(stackless_theme_blocks_card_fresh($type, $key), static fn($value): bool => $value !== '');
    return $fresh ? array_merge($attributes, $fresh) : $attributes;
}

/**
 * Whether a key is a name its source could hold: a WordPress.org slug, an owner/repo pair or an
 * npm package name. Anything else is never looked up, so a mistyped value schedules no request.
 */
function stackless_theme_blocks_card_valid_key(string $type, string $key): bool
{
    $patterns = [
        'plugin' => '/^[a-z0-9][a-z0-9-]*$/',
        'theme' => '/^[a-z0-9][a-z0-9-]*$/',
        'github' => '~^[\w.-]+/[\w.-]+$~',
        'npm' => '~^(@[a-z0-9-][a-z0-9._-]*/)?[a-z0-9-][a-z0-9._-]*$~i',
    ];
    return isset($patterns[$type]) && $key !== '' && strlen($key) <= 214 && preg_match($patterns[$type], $key) === 1;
}

/** The cached fresh fields, or an empty array after scheduling a fetch. */
function stackless_theme_blocks_card_fresh(string $type, string $key): array
{
    $cached = get_transient(stackless_theme_blocks_card_cache_key($type, $key));
    if (is_array($cached)) {
        return empty($cached['_failed']) ? $cached : [];
    }
    $args = [$type, $key];
    if (!wp_next_scheduled('stackless_theme_blocks_refresh_card', $args)) {
        wp_schedule_single_event(time() + 5, 'stackless_theme_blocks_refresh_card', $args);
    }
    return [];
}

function stackless_theme_blocks_card_cache_key(string $type, string $key): string
{
    return 'stackless_card_' . $type . '_' . md5(strtolower($key));
}

/** Cron: fetch and cache one card's fields. A failure is remembered for six hours. */
function stackless_theme_blocks_card_refresh($type, $key): void
{
    $type = (string) $type;
    $key = (string) $key;
    $fields = stackless_theme_blocks_card_fetch($type, $key);
    if ($fields) {
        set_transient(stackless_theme_blocks_card_cache_key($type, $key), $fields, DAY_IN_SECONDS);
    } else {
        set_transient(stackless_theme_blocks_card_cache_key($type, $key), ['_failed' => 1], 6 * HOUR_IN_SECONDS);
    }
}

/** GETs a JSON document; null on any failure. */
function stackless_theme_blocks_card_json(string $url): ?array
{
    $response = wp_safe_remote_get($url, [
        'timeout' => 5,
        'headers' => ['Accept' => 'application/json', 'User-Agent' => 'Stackless-Theme-Blocks'],
    ]);
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    return is_array($data) ? $data : null;
}

/** npm's compact count: 1.2k, 3M. */
function stackless_theme_blocks_card_compact_number($number): string
{
    $number = (int) $number;
    if ($number >= 1000000) {
        return preg_replace('/\.0$/', '', number_format($number / 1000000, 1, '.', '')) . 'M';
    }
    if ($number >= 1000) {
        return preg_replace('/\.0$/', '', number_format($number / 1000, 1, '.', '')) . 'k';
    }
    return (string) $number;
}

/**
 * Today's volatile fields for one card, in block-attribute form and in the units and formats
 * the editor scripts save, so a refreshed card reads like a freshly inserted one.
 *
 * @return array Empty on failure.
 */
function stackless_theme_blocks_card_fetch(string $type, string $key): array
{
    if (!stackless_theme_blocks_card_valid_key($type, $key)) {
        return [];
    }
    switch ($type) {
        case 'plugin':
        case 'theme':
            $data = stackless_theme_blocks_card_json('https://api.wordpress.org/' . $type . 's/info/1.2/?action=' . $type . '_information&slug=' . rawurlencode($key));
            if (!$data || !empty($data['error']) || empty($data['version'])) {
                return [];
            }
            $fields = [
                'rating' => !empty($data['rating']) ? round(((float) $data['rating'] / 20) * 2) / 2 : 0,
                'ratingCount' => (int) ($data['num_ratings'] ?? 0),
                'activeInstalls' => !empty($data['active_installs']) ? number_format((int) $data['active_installs']) : '',
                'version' => sanitize_text_field((string) $data['version']),
                'requiresWP' => sanitize_text_field((string) ($data['requires'] ?? '')),
                'requiresPHP' => sanitize_text_field((string) ($data['requires_php'] ?? '')),
            ];
            if ($type === 'plugin') {
                $fields['testedUpTo'] = sanitize_text_field((string) ($data['tested'] ?? ''));
            }
            return $fields;

        case 'github':
            $data = stackless_theme_blocks_card_json('https://api.github.com/repos/' . $key);
            if (!$data || !isset($data['stargazers_count'])) {
                return [];
            }
            return [
                'stars' => (int) $data['stargazers_count'],
                'forks' => (int) ($data['forks_count'] ?? 0),
                'openIssues' => (int) ($data['open_issues_count'] ?? 0),
                'license' => sanitize_text_field((string) ($data['license']['spdx_id'] ?? '')),
                'lastUpdated' => sanitize_text_field((string) ($data['updated_at'] ?? '')),
            ];

        case 'npm':
            // A scoped name keeps its @ and encodes only the slash, as the registry expects.
            $package = str_replace('%40', '@', rawurlencode($key));
            $data = stackless_theme_blocks_card_json('https://registry.npmjs.org/' . $package);
            $version = is_array($data) ? sanitize_text_field((string) ($data['dist-tags']['latest'] ?? '')) : '';
            if ($version === '') {
                return [];
            }
            $license = $data['license'] ?? '';
            $fields = [
                'version' => $version,
                'license' => sanitize_text_field(is_string($license) ? $license : (string) ($license['type'] ?? '')),
            ];
            $time = !empty($data['time'][$version]) ? strtotime((string) $data['time'][$version]) : false;
            if ($time) {
                $fields['lastPublished'] = gmdate('M j, Y', $time);
            }
            $downloads = stackless_theme_blocks_card_json('https://api.npmjs.org/downloads/point/last-week/' . $package);
            if (isset($downloads['downloads'])) {
                $fields['weeklyDownloads'] = stackless_theme_blocks_card_compact_number($downloads['downloads']);
            }
            return $fields;
    }
    return [];
}

/* ------------------------------------------------------------------ Editor endpoints */

/** The nonce and URL the card editor scripts post to. */
function stackless_theme_blocks_card_editor_data(): array
{
    return [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('stackless_card_image'),
    ];
}

/**
 * The unique name of a card image from the request: "plugin-jetpack-icon". Sends a JSON error
 * and stops when the request is not allowed or is incomplete.
 */
function stackless_theme_blocks_card_image_uid(): string
{
    check_ajax_referer('stackless_card_image', 'nonce');
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('You do not have permission to upload files.', 'stackless-theme'), 403);
    }
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
    $type = isset($_POST['type']) ? sanitize_key(wp_unslash($_POST['type'])) : '';
    $slug = isset($_POST['slug']) ? sanitize_file_name(wp_unslash($_POST['slug'])) : '';
    $image_type = isset($_POST['image_type']) ? sanitize_key(wp_unslash($_POST['image_type'])) : '';
    // phpcs:enable
    if (!in_array($type, ['plugin', 'theme'], true) || $slug === '' || !in_array($image_type, ['icon', 'banner', 'screenshot'], true)) {
        wp_send_json_error(__('Missing parameters.', 'stackless-theme'), 400);
    }
    return $type . '-' . $slug . '-' . $image_type;
}

/**
 * The attachment a card image was imported as, or 0, so the same image is not downloaded twice.
 */
function stackless_theme_blocks_card_image_id(string $uid): int
{
    $found = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- editor-only lookup.
            ['key' => '_stackless_card_uid', 'value' => $uid],
        ],
    ]);
    return $found ? (int) $found[0] : 0;
}

/** Whether a Plugin or Theme card's image is already in the media library. */
add_action('wp_ajax_stackless_check_card_image', 'stackless_theme_blocks_card_check_image');
function stackless_theme_blocks_card_check_image(): void
{
    $id = stackless_theme_blocks_card_image_id(stackless_theme_blocks_card_image_uid());
    if ($id) {
        wp_send_json_success(['exists' => true, 'url' => wp_get_attachment_url($id), 'id' => $id]);
    }
    wp_send_json_success(['exists' => false]);
}

/**
 * Copies a Plugin or Theme card's icon, banner or screenshot from WordPress.org into the media
 * library, so the card serves it from this site. Only WordPress.org's image hosts are accepted.
 */
add_action('wp_ajax_stackless_import_card_image', 'stackless_theme_blocks_card_import_image');
function stackless_theme_blocks_card_import_image(): void
{
    $uid = stackless_theme_blocks_card_image_uid();
    $image_url = isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url']), ['https']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in the uid helper.
    $host = $image_url !== '' ? wp_parse_url($image_url, PHP_URL_HOST) : '';
    if (!in_array($host, ['ps.w.org', 's.w.org', 'i0.wp.com', 'ts.w.org'], true)) {
        wp_send_json_error(__('Invalid image source.', 'stackless-theme'), 400);
    }

    $id = stackless_theme_blocks_card_image_id($uid);
    if ($id) {
        wp_send_json_success(['url' => wp_get_attachment_url($id), 'id' => $id]);
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($image_url, 30);
    if (is_wp_error($tmp)) {
        wp_send_json_error($tmp->get_error_message());
    }
    $extension = strtolower((string) pathinfo((string) wp_parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'], true)) {
        $extension = 'png';
    }
    [$type, $rest] = explode('-', $uid, 2);
    $title = ucwords($type) . ': ' . ucwords(str_replace('-', ' ', $rest));

    // Sideloading keeps the site's year and month folders and its upload rules: a file type the
    // site does not allow (SVG, by default) is refused and the card keeps the WordPress.org URL.
    $id = media_handle_sideload(['name' => $uid . '.' . $extension, 'tmp_name' => $tmp], 0, $title);
    if (is_wp_error($id)) {
        if (is_string($tmp) && file_exists($tmp)) {
            wp_delete_file($tmp);
        }
        wp_send_json_error($id->get_error_message());
    }
    update_post_meta($id, '_stackless_card_uid', $uid);
    wp_send_json_success(['url' => wp_get_attachment_url($id), 'id' => $id]);
}

/** Reads one episode from a podcast feed for the Podcast card. Cached for an hour. */
add_action('wp_ajax_stackless_fetch_podcast_episode', 'stackless_theme_blocks_card_podcast_episode');
function stackless_theme_blocks_card_podcast_episode(): void
{
    check_ajax_referer('stackless_card_image', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('You do not have permission to fetch feeds.', 'stackless-theme'), 403);
    }
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
    $feed_url = isset($_POST['feed_url']) ? esc_url_raw(wp_unslash($_POST['feed_url']), ['http', 'https']) : '';
    $index = isset($_POST['episode_index']) ? max(0, (int) $_POST['episode_index']) : 0;
    // phpcs:enable
    if ($feed_url === '' || !wp_http_validate_url($feed_url)) {
        wp_send_json_error(__('Invalid feed URL.', 'stackless-theme'));
    }

    $cache_key = 'stackless_podcast_' . md5($feed_url . '_' . $index);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
    }
    if (!function_exists('simplexml_load_string')) {
        wp_send_json_error(__('This server cannot read feeds: PHP\'s SimpleXML extension is missing.', 'stackless-theme'));
    }

    // wp_safe_remote_get() refuses private and local addresses, and certificate checks stay on.
    // Five seconds is plenty for a feed and keeps a slow host from hanging the editor.
    $response = wp_safe_remote_get($feed_url, ['timeout' => 5]);
    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    $previous = libxml_use_internal_errors(true);
    $xml = simplexml_load_string(wp_remote_retrieve_body($response), 'SimpleXMLElement', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if ($xml === false || !isset($xml->channel)) {
        wp_send_json_error(__('Failed to parse feed XML.', 'stackless-theme'));
    }

    $channel = $xml->channel;
    $items = $channel->item;
    if (!isset($items[$index])) {
        wp_send_json_error(__('Episode not found at the specified index.', 'stackless-theme'));
    }
    $item = $items[$index];
    $ns = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
    $itunes = $item->children($ns);

    $description = (string) $itunes->summary !== '' ? (string) $itunes->summary : (string) $item->description;
    $description = wp_strip_all_tags($description);
    if (function_exists('mb_strlen') ? mb_strlen($description) > 200 : strlen($description) > 200) {
        $description = (function_exists('mb_substr') ? mb_substr($description, 0, 200) : substr($description, 0, 200)) . '...';
    }

    $artwork = isset($itunes->image) ? (string) $itunes->image->attributes()['href'] : '';
    if ($artwork === '') {
        $channel_itunes = $channel->children($ns);
        $artwork = isset($channel_itunes->image) ? (string) $channel_itunes->image->attributes()['href'] : '';
    }
    $published = isset($item->pubDate) ? strtotime((string) $item->pubDate) : false;

    $data = [
        'podcastTitle' => sanitize_text_field((string) $channel->title),
        'episodeTitle' => sanitize_text_field((string) $item->title),
        'episodeDescription' => sanitize_text_field($description),
        'artworkUrl' => esc_url_raw($artwork, ['http', 'https']),
        'duration' => sanitize_text_field((string) $itunes->duration),
        'publishDate' => $published ? gmdate('Y-m-d', $published) : '',
        'audioUrl' => isset($item->enclosure) ? esc_url_raw((string) $item->enclosure->attributes()['url'], ['http', 'https']) : '',
        'episodeUrl' => esc_url_raw((string) $item->link, ['http', 'https']),
    ];
    set_transient($cache_key, $data, HOUR_IN_SECONDS);
    wp_send_json_success($data);
}
