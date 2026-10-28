<?php
/**
 * Store toolbar: a promo line, search and sorting, the currency switcher, category chips and quick
 * links above the product grid. Easy Digital Downloads shows it on the store page and the download
 * archives (on by default); WooCommerce shows it above the shop loop only when it is switched on
 * under Customize > Store (WooCommerce), so existing shops are unchanged.
 *
 * Settings live in Appearance > Customize > Store (Easy Digital Downloads) and Store (WooCommerce);
 * each section appears only while its plugin is active.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/** Default value of every store setting, per store ('edd' or 'woo'). */
function stackless_theme_store_defaults(string $store): array
{
    $edd = $store === 'edd';
    return [
        'toolbar' => $edd,
        'currency' => 'toolbar',
        'chips' => true,
        'sort_search' => $edd,
        'links' => true,
        'promo_text' => '',
        'promo_code' => '',
    ];
}

/** A store setting, e.g. stackless_theme_store_option('edd', 'chips'). */
function stackless_theme_store_option(string $store, string $key)
{
    $defaults = stackless_theme_store_defaults($store);
    $value = get_theme_mod('stackless_' . $store . '_store_' . $key, $defaults[$key] ?? null);
    return apply_filters('stackless_theme_store_option', $value, $store, $key);
}

/** The page picked as the EDD store page: the Customizer choice, else a published page with the slug "store". */
function stackless_theme_edd_store_page_id(): int
{
    $id = (int) get_theme_mod('stackless_edd_store_page', 0);
    if ($id < 1) {
        $page = get_page_by_path('store');
        $id = $page instanceof WP_Post && $page->post_status === 'publish' ? (int) $page->ID : 0;
    }
    return (int) apply_filters('stackless_theme_edd_store_page_id', $id);
}

/* ------------------------------------------------------------------ Pieces */

/** Category chips: top-level categories with something in them. EDD hides the categories Stackless keeps for signed-in buyers. */
function stackless_theme_store_terms(string $store): array
{
    $taxonomy = $store === 'edd' ? 'download_category' : 'product_cat';
    $args = ['taxonomy' => $taxonomy, 'hide_empty' => true, 'parent' => 0, 'orderby' => 'name'];
    if ($store === 'woo') {
        $args['exclude'] = [(int) get_option('default_product_cat')];
    }
    $terms = get_terms($args);
    if (is_wp_error($terms)) {
        return [];
    }
    if ($store === 'edd' && !is_user_logged_in() && function_exists('stackless_edd_module_active') && stackless_edd_module_active('hide_price_until_login')
        && get_option('stackless_edd_hide_price_until_login_scope', '') === 'categories') {
        $hidden = array_map('intval', (array) get_option('stackless_edd_hide_price_until_login_categories', []));
        $terms = array_values(array_filter($terms, static function ($term) use ($hidden) {
            return !in_array((int) $term->term_id, $hidden, true);
        }));
    }
    return (array) apply_filters('stackless_theme_store_terms', $terms, $store);
}

/** The archive every store search, sort and "All" chip points at. */
function stackless_theme_store_archive_url(string $store): string
{
    if ($store === 'edd') {
        return (string) get_post_type_archive_link('download');
    }
    return function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('shop') : '';
}

/** Quick links: wishlist, saved carts, request a quote and pricing, each only when it exists. */
function stackless_theme_store_links(string $store): array
{
    $links = [];
    $page = static function ($id): string {
        $id = (int) $id;
        return $id > 0 && get_post_status($id) === 'publish' ? (string) get_permalink($id) : '';
    };
    $active = static function (string $module) use ($store): bool {
        if ($store === 'edd') {
            return function_exists('stackless_edd_module_active') && stackless_edd_module_active($module);
        }
        return function_exists('stackless_module_active') ? (bool) stackless_module_active($module) : false;
    };
    if ($store === 'edd') {
        if ($active('wishlist') && ($url = $page(get_option('stackless_edd_wishlist_page_id')))) {
            $links['wishlist'] = ['label' => __('Wishlist', 'stackless-theme'), 'icon' => 'heart', 'url' => $url];
        }
        if ($active('saved_carts') && is_user_logged_in() && function_exists('stackless_edd_account_url')) {
            $links['saved-carts'] = ['label' => __('Saved carts', 'stackless-theme'), 'icon' => 'shopping-bag', 'url' => stackless_edd_account_url('saved-carts')];
        }
        if ($active('request_quotes') && ($url = $page(get_option('stackless_edd_request_quotes_page')))) {
            $links['quote'] = ['label' => __('Request a quote', 'stackless-theme'), 'icon' => 'message-circle', 'url' => $url];
        }
    } else {
        foreach (['wishlist' => ['wishlist', __('Wishlist', 'stackless-theme'), 'heart'], 'quote' => ['request-a-quote', __('Request a quote', 'stackless-theme'), 'message-circle']] as $key => [$slug, $label, $icon]) {
            $p = get_page_by_path($slug);
            if ($p instanceof WP_Post && $p->post_status === 'publish') {
                $links[$key] = ['label' => $label, 'icon' => $icon, 'url' => (string) get_permalink($p)];
            }
        }
    }
    $pricing = get_page_by_path('pricing');
    if ($pricing instanceof WP_Post && $pricing->post_status === 'publish' && (int) $pricing->ID !== (int) get_queried_object_id()) {
        $links['pricing'] = ['label' => __('Pricing', 'stackless-theme'), 'icon' => 'tag', 'url' => (string) get_permalink($pricing)];
    }
    return (array) apply_filters('stackless_theme_store_links', $links, $store);
}

/** The currency switcher from Stackless (EDD or WooCommerce), or ''. */
function stackless_theme_store_currency(string $store): string
{
    $tag = $store === 'edd' ? 'stackless_edd_currency_selector' : 'stackless_currency_selector';
    if (!shortcode_exists($tag)) {
        return '';
    }
    if ($store === 'edd' && function_exists('stackless_edd_module_active') && !stackless_edd_module_active('currency_selector')) {
        return '';
    }
    return (string) do_shortcode('[' . $tag . ' show_label="no" format="code_symbol" class="st-store-currency"]');
}

/** Sort choices for the EDD download archives. */
function stackless_theme_edd_sorts(): array
{
    return [
        'newest' => __('Newest', 'stackless-theme'),
        'popular' => __('Most popular', 'stackless-theme'),
        'price' => __('Price: low to high', 'stackless-theme'),
        'price-desc' => __('Price: high to low', 'stackless-theme'),
    ];
}

function stackless_theme_edd_current_sort(): string
{
    $sort = isset($_GET['sort']) ? sanitize_key(wp_unslash($_GET['sort'])) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only sorting.
    return isset(stackless_theme_edd_sorts()[$sort]) ? $sort : 'newest';
}

/** Prints the store toolbar for 'edd' or 'woo'. $current_term is the category being viewed, if any. */
function stackless_theme_store_toolbar(string $store, int $current_term = 0): void
{
    if (!stackless_theme_store_option($store, 'toolbar')) {
        return;
    }
    $promo_text = trim((string) stackless_theme_store_option($store, 'promo_text'));
    $promo_code = trim((string) stackless_theme_store_option($store, 'promo_code'));
    $currency_at = (string) stackless_theme_store_option($store, 'currency');
    $currency = $currency_at !== 'off' ? stackless_theme_store_currency($store) : '';
    $search = $store === 'edd' && stackless_theme_store_option($store, 'sort_search');
    $terms = stackless_theme_store_option($store, 'chips') ? stackless_theme_store_terms($store) : [];
    $links = stackless_theme_store_option($store, 'links') ? stackless_theme_store_links($store) : [];
    $archive = stackless_theme_store_archive_url($store);
    $in_archive = $store === 'edd' ? (is_post_type_archive('download') || is_tax('download_category')) && !is_search() : (function_exists('is_shop') && is_shop());

    if ($promo_text === '' && $promo_code === '' && !$currency && !$search && !$terms && !$links) {
        return;
    }
    ?>
	<div class="st-store-bar st-store-bar--<?php echo esc_attr($store); ?>">
		<?php if ($promo_text !== '' || $promo_code !== '') : ?>
			<p class="st-store-promo">
				<?php stackless_theme_the_icon('tag', 18); ?>
				<span class="st-store-promo__text"><?php echo esc_html($promo_text); ?></span>
				<?php if ($promo_code !== '') : ?>
					<span class="st-store-promo__code"><span class="screen-reader-text"><?php esc_html_e('Code:', 'stackless-theme'); ?> </span><code><?php echo esc_html($promo_code); ?></code></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<?php if ($search || ($currency && $currency_at === 'toolbar')) : ?>
			<div class="st-store-toolbar">
				<?php if ($search && $archive) : ?>
					<form class="st-store-search" role="search" method="get" action="<?php echo esc_url($archive); ?>">
						<label class="screen-reader-text" for="st-store-search-field"><?php esc_html_e('Search downloads', 'stackless-theme'); ?></label>
						<span class="st-store-search__field">
							<?php stackless_theme_the_icon('search', 18); ?>
							<input type="search" id="st-store-search-field" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Search downloads', 'stackless-theme'); ?>">
						</span>
						<input type="hidden" name="post_type" value="download">
						<label class="screen-reader-text" for="st-store-sort"><?php esc_html_e('Sort by', 'stackless-theme'); ?></label>
						<select id="st-store-sort" class="st-store-sort" name="sort" onchange="this.form.submit()">
							<?php foreach (stackless_theme_edd_sorts() as $value => $label) : ?>
								<option value="<?php echo esc_attr($value); ?>"<?php selected(stackless_theme_edd_current_sort(), $value); ?>><?php echo esc_html($label); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="st-store-search__submit"><?php esc_html_e('Search', 'stackless-theme'); ?></button>
					</form>
				<?php endif; ?>
				<?php if ($currency && $currency_at === 'toolbar') : ?>
					<div class="st-store-currency-wrap"><?php echo $currency; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the plugin escapes its selector. ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ($terms || $links || ($currency && $currency_at === 'chips')) : ?>
			<div class="st-store-nav">
				<?php if ($terms) : ?>
					<nav class="st-store-chips" aria-label="<?php esc_attr_e('Categories', 'stackless-theme'); ?>">
						<?php if ($archive) : ?>
							<a class="chip<?php echo $in_archive && !$current_term ? ' is-current' : ''; ?>" href="<?php echo esc_url($archive); ?>"<?php echo $in_archive && !$current_term ? ' aria-current="page"' : ''; ?>><?php esc_html_e('All', 'stackless-theme'); ?></a>
						<?php endif; ?>
						<?php foreach ($terms as $term) : ?>
							<?php $is = $current_term === (int) $term->term_id; ?>
							<a class="chip<?php echo $is ? ' is-current' : ''; ?>" href="<?php echo esc_url(get_term_link($term)); ?>"<?php echo $is ? ' aria-current="page"' : ''; ?>><?php echo esc_html($term->name); ?></a>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>
				<?php if ($links || ($currency && $currency_at === 'chips')) : ?>
					<div class="st-store-links">
						<?php foreach ($links as $key => $link) : ?>
							<a class="st-store-link st-store-link--<?php echo esc_attr($key); ?>" href="<?php echo esc_url($link['url']); ?>"><?php stackless_theme_the_icon($link['icon'], 16); ?><?php echo esc_html($link['label']); ?></a>
						<?php endforeach; ?>
						<?php if ($currency && $currency_at === 'chips') : ?>
							<div class="st-store-currency-wrap"><?php echo $currency; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the plugin escapes its selector. ?></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/* ------------------------------------------------------------------ Easy Digital Downloads wiring */

/** The store page gets the toolbar between its title and its blocks. */
add_filter('the_content', 'stackless_theme_edd_store_page_toolbar', 20);
function stackless_theme_edd_store_page_toolbar(string $content): string
{
    if (!class_exists('Easy_Digital_Downloads') || !is_page() || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    $store = stackless_theme_edd_store_page_id();
    if ($store < 1 || (int) get_the_ID() !== $store || (int) get_queried_object_id() !== $store) {
        return $content;
    }
    ob_start();
    stackless_theme_store_toolbar('edd');
    return (string) ob_get_clean() . $content;
}

/** A download search uses the download archive template. */
add_filter('template_include', 'stackless_theme_edd_search_template', 20);
function stackless_theme_edd_search_template(string $template): string
{
    if (class_exists('Easy_Digital_Downloads') && is_search() && get_query_var('post_type') === 'download') {
        $archive = locate_template('archive-download.php');
        if ($archive) {
            return $archive;
        }
    }
    return $template;
}

/** Sorting on the download archives, categories, tags and download search. */
add_action('pre_get_posts', 'stackless_theme_edd_store_sort', 20);
function stackless_theme_edd_store_sort(WP_Query $query): void
{
    if (is_admin() || !$query->is_main_query() || !class_exists('Easy_Digital_Downloads')) {
        return;
    }
    $downloads = $query->is_post_type_archive('download') || $query->is_tax(['download_category', 'download_tag'])
        || ($query->is_search() && $query->get('post_type') === 'download');
    if (!$downloads) {
        return;
    }
    if ($query->is_search()) {
        $query->set('posts_per_page', (int) apply_filters('stackless_theme_downloads_per_page', 12));
    }
    switch (stackless_theme_edd_current_sort()) {
        case 'price':
        case 'price-desc':
            $query->set('meta_key', 'edd_price');
            $query->set('orderby', ['meta_value_num' => stackless_theme_edd_current_sort() === 'price' ? 'ASC' : 'DESC', 'title' => 'ASC']);
            break;
        case 'popular':
            $query->set('meta_key', '_edd_download_sales');
            $query->set('orderby', ['meta_value_num' => 'DESC', 'date' => 'DESC']);
            break;
    }
}

/* ------------------------------------------------------------------ WooCommerce wiring (off unless switched on) */

add_action('woocommerce_before_shop_loop', 'stackless_theme_woo_store_toolbar', 4);
function stackless_theme_woo_store_toolbar(): void
{
    if (!stackless_theme_store_option('woo', 'toolbar')) {
        return;
    }
    $term = is_product_category() ? (int) get_queried_object_id() : 0;
    stackless_theme_store_toolbar('woo', $term);
}

/* ------------------------------------------------------------------ Customizer */

add_action('customize_register', 'stackless_theme_store_customize_register', 20);
function stackless_theme_store_customize_register(WP_Customize_Manager $wp_customize): void
{
    $stores = [];
    if (class_exists('Easy_Digital_Downloads')) {
        $stores['edd'] = [
            'title' => __('Store (Easy Digital Downloads)', 'stackless-theme'),
            'description' => __('The toolbar above the download grid on the store page and the download archives.', 'stackless-theme'),
        ];
    }
    if (class_exists('WooCommerce')) {
        $stores['woo'] = [
            'title' => __('Store (WooCommerce)', 'stackless-theme'),
            'description' => __('An optional toolbar above the shop grid. Off by default; WooCommerce keeps its own sorting and result count.', 'stackless-theme'),
        ];
    }
    foreach ($stores as $store => $section) {
        $id = 'stackless_theme_store_' . $store;
        $wp_customize->add_section($id, $section + ['priority' => 31]);
        $defaults = stackless_theme_store_defaults($store);
        $setting = static function (string $key, $sanitize) use ($wp_customize, $store, $defaults): string {
            $name = 'stackless_' . $store . '_store_' . $key;
            $wp_customize->add_setting($name, ['default' => $defaults[$key] ?? '', 'sanitize_callback' => $sanitize]);
            return $name;
        };
        if ($store === 'edd') {
            $wp_customize->add_setting('stackless_edd_store_page', ['default' => 0, 'sanitize_callback' => 'absint']);
            $wp_customize->add_control('stackless_edd_store_page', [
                'label' => __('Store page', 'stackless-theme'),
                'description' => __('Gets the toolbar above its blocks. Empty uses the page with the slug "store".', 'stackless-theme'),
                'section' => $id,
                'type' => 'dropdown-pages',
            ]);
        }
        $wp_customize->add_control($setting('toolbar', 'rest_sanitize_boolean'), ['label' => __('Show the store toolbar', 'stackless-theme'), 'section' => $id, 'type' => 'checkbox']);
        $wp_customize->add_control($setting('currency', 'stackless_theme_sanitize_store_currency'), [
            'label' => __('Currency switcher', 'stackless-theme'),
            'description' => __('Shown only while the Stackless Currency Selector module is on.', 'stackless-theme'),
            'section' => $id,
            'type' => 'select',
            'choices' => [
                'toolbar' => __('Beside the search and sorting', 'stackless-theme'),
                'chips' => __('Beside the category chips', 'stackless-theme'),
                'off' => __('Hidden', 'stackless-theme'),
            ],
        ]);
        $wp_customize->add_control($setting('chips', 'rest_sanitize_boolean'), ['label' => __('Category chips', 'stackless-theme'), 'section' => $id, 'type' => 'checkbox']);
        if ($store === 'edd') {
            $wp_customize->add_control($setting('sort_search', 'rest_sanitize_boolean'), ['label' => __('Search and sorting', 'stackless-theme'), 'section' => $id, 'type' => 'checkbox']);
        }
        $wp_customize->add_control($setting('links', 'rest_sanitize_boolean'), [
            'label' => __('Quick links', 'stackless-theme'),
            'description' => __('Wishlist, saved carts, request a quote and pricing, when those pages exist.', 'stackless-theme'),
            'section' => $id,
            'type' => 'checkbox',
        ]);
        $wp_customize->add_control($setting('promo_text', 'sanitize_text_field'), [
            'label' => __('Promo line', 'stackless-theme'),
            'description' => __('For example: 10% off your first order.', 'stackless-theme'),
            'section' => $id,
            'type' => 'text',
        ]);
        $wp_customize->add_control($setting('promo_code', 'stackless_theme_sanitize_promo_code'), [
            'label' => __('Promo code', 'stackless-theme'),
            'section' => $id,
            'type' => 'text',
        ]);
    }
}

function stackless_theme_sanitize_store_currency($value): string
{
    return in_array($value, ['toolbar', 'chips', 'off'], true) ? $value : 'toolbar';
}

function stackless_theme_sanitize_promo_code($value): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value));
}
