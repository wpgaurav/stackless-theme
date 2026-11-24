<?php
/**
 * Render callbacks for Stackless blocks. Each prints the block's markup from its attributes.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

function stackless_theme_blocks_render_arrow(array $attributes, string $content = ''): string
{
    $icon = isset( $attributes['icon'] ) ? $attributes['icon'] : 'stb-icon-angle-down';
    return '<p class="arrow-down text-center"><span class="' . esc_attr( $icon ) . ' large-title"></span></p>';
}

function stackless_theme_blocks_render_callout(array $attributes, string $content = ''): string
{
    ob_start();
    $title = stackless_theme_blocks_field( $attributes, 'title' );
    $text = stackless_theme_blocks_field( $attributes, 'text' );
    $list = stackless_theme_blocks_field( $attributes, 'list' );
    $icon = stackless_theme_blocks_field( $attributes, 'icon' );
    $iconImage = stackless_theme_blocks_field( $attributes, 'iconImage' );
    $buttonText = stackless_theme_blocks_field( $attributes, 'buttonText' );
    $popup = stackless_theme_blocks_field( $attributes, 'popup' );
    $url = stackless_theme_blocks_field( $attributes, 'url' );
    $href = ! empty( $url ) ? $url : '#';
    $align = stackless_theme_blocks_field( $attributes, 'align' );
    $alignment = stackless_theme_blocks_field( $attributes, 'alignment' );
    $marginBottom = stackless_theme_blocks_field( $attributes, 'marginBottom' );
    $padding = stackless_theme_blocks_field( $attributes, 'padding' );
    $shadow = stackless_theme_blocks_field( $attributes, 'shadow' );
    $className = stackless_theme_blocks_field( $attributes, 'className' );
    $preset = stackless_theme_blocks_field( $attributes, 'preset' );
    $layoutStyle = stackless_theme_blocks_field( $attributes, 'layoutStyle' );
    $borderColor = stackless_theme_blocks_field( $attributes, 'borderColor' );
    $bgColor = stackless_theme_blocks_field( $attributes, 'bgColor' );
    $bgColorClass = stackless_theme_blocks_field( $attributes, 'bgColorClass' );
    $bgImage = stackless_theme_blocks_field( $attributes, 'bgImage' );
    $iconColor = stackless_theme_blocks_field( $attributes, 'iconColor' );
    $textColor = stackless_theme_blocks_field( $attributes, 'textColor' );
    $textColorClass = stackless_theme_blocks_field( $attributes, 'textColorClass' );
    $buttonColor = stackless_theme_blocks_field( $attributes, 'buttonColor' );
    $buttonColorClass = stackless_theme_blocks_field( $attributes, 'buttonColorClass' );
    $buttonTextColor = stackless_theme_blocks_field( $attributes, 'buttonTextColor' );
    $buttonTextColorClass = stackless_theme_blocks_field( $attributes, 'buttonTextColorClass' );
    $has_popup = ! empty( $popup ) && empty( $url );
    $popup_data = $has_popup ? stackless_theme_blocks_popup_trigger( (string) $popup, 'callout' ) : '';
    $has_popup = $popup_data !== '';

    $preset_colors = array(
        'tip'     => array( 'border' => '#22c55e', 'bg' => '#f0fdf4' ),
        'info'    => array( 'border' => '#3b82f6', 'bg' => '#eff6ff' ),
        'warning' => array( 'border' => '#f59e0b', 'bg' => '#fffbeb' ),
        'success' => array( 'border' => '#10b981', 'bg' => '#ecfdf5' ),
        'error'   => array( 'border' => '#ef4444', 'bg' => '#fef2f2' ),
    );

    $style_parts = array();
    if ( ! empty( $preset ) && isset( $preset_colors[ $preset ] ) ) {
        $style_parts[] = 'border-color: ' . $preset_colors[ $preset ]['border'];
        $style_parts[] = 'background-color: ' . $preset_colors[ $preset ]['bg'];
    } else {
        if ( empty( $bgColorClass ) && ! empty( $bgColor ) )
            $style_parts[] = 'background-color: ' . esc_attr( $bgColor );
        if ( ! empty( $borderColor ) )
            $style_parts[] = 'border-color: ' . esc_attr( $borderColor );
        if ( ! empty( $textColor ) && empty( $textColorClass ) )
            $style_parts[] = 'color: ' . esc_attr( $textColor );
    }
    if ( ! empty( $bgImage ) )
        $style_parts[] = "background-image: url('" . esc_url( $bgImage ) . "')";
    $style = ! empty( $style_parts ) ? ' style="' . implode( '; ', $style_parts ) . ';"' : '';

    $has_buttonColor = ! empty( $buttonColor ) && empty( $buttonColorClass );
    $has_buttonTextColor = ! empty( $buttonTextColor ) && empty( $buttonTextColorClass );

    $classes = $button_classes = array();
    $classes[] = 'callout';
    if ( ! empty( $preset ) )
        $classes[] = 'callout-' . esc_attr( $preset );
    if ( ! empty( $align ) )
        $classes[] = "align{$align}";
    if ( empty( $preset ) ) {
        $classes[] = ! empty( $bgColorClass ) ? $bgColorClass : '';
        $classes[] = ! empty( $textColorClass ) ? $textColorClass : '';
    }
    $classes[] = ! empty( $marginBottom ) ? $marginBottom : 'mb-double';
    $classes[] = ! empty( $padding ) ? $padding : 'block-mid';
    if ( ! empty( $className ) )
        $classes[] = $className;
    if ( ! empty( $icon ) || ! empty( $iconImage ) )
        $classes[] = 'has-icon';
    if ( ! empty( $alignment ) )
        $classes[] = 'text-' . esc_attr( $alignment );
    if ( ! empty( $shadow ) )
        $classes[] = 'shadow-small';
    if ( ! empty( $bgImage ) )
        $classes[] = 'image-overlay';
    if ( ! empty( $layoutStyle ) && $layoutStyle !== 'tinted' )
        $classes[] = 'callout-layout-' . esc_attr( $layoutStyle );
    $classes[] = 'mt-double';
    $classes = join( ' ', array_filter( $classes ) );

    if ( ! empty( $buttonTextColorClass ) )
        $button_classes[] = $buttonTextColorClass;
    if ( $has_popup )
        $button_classes[] = 'stb-popup-trigger';
    $button_classes[] = ! empty( $buttonColorClass ) ? $buttonColorClass : '';
    $button_classes = join( ' ', array_filter( $button_classes ) );

    $block_id = 'callout-' . wp_unique_id();
    $title_id = $block_id . '-title';
    $has_title = ! empty( $title );
?>
<aside class="<?php echo esc_attr( $classes ); ?>" role="complementary"<?php echo $style; ?><?php echo $has_title ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : ''; ?>>
    <?php if ( ! empty( $icon ) ) : ?>
        <div class="callout-icon icon mb-single" aria-hidden="true"<?php echo ! empty( $iconColor ) ? ' style="background-color: ' . esc_attr( $iconColor ) . ';"' : ''; ?>>
            <i class="<?php echo esc_attr( $icon ); ?>"></i>
        </div>
    <?php elseif ( ! empty( $iconImage ) ) : ?>
        <div class="callout-icon image mb-single">
            <img src="<?php echo esc_url( $iconImage ); ?>" height="100" width="100" alt="" role="presentation" />
        </div>
    <?php endif; ?>
    <?php if ( $has_title ) : ?>
        <p id="<?php echo esc_attr( $title_id ); ?>" class="callout-title med-title mb-single"><?php echo stackless_theme_blocks_text( $title ); ?></p>
    <?php endif; ?>
    <?php if ( $text ) : ?>
        <div class="callout-text mb-single">
            <?php echo wp_kses_post( wpautop( $text ) ); ?>
        </div>
    <?php endif; ?>
    <?php if ( $list ) : ?>
        <ul class="callout-list mb-single">
            <?php echo wp_kses_post( wpautop( $list ) ); ?>
        </ul>
    <?php endif; ?>
    <?php if ( ! empty( $buttonText ) && ( ! empty( $url ) || empty( $popup ) || $has_popup ) ) :
        $button_bg = $has_buttonColor ? 'background-color: ' . esc_attr( $buttonColor ) . ';' : '';
        $button_color = $has_buttonTextColor ? ' color: ' . esc_attr( $buttonTextColor ) . ';' : '';
        $button_style = $has_buttonColor || $has_buttonTextColor ? ' style="' . $button_bg . $button_color . '"' : '';
    ?>
        <p class="callout-action">
            <a href="<?php echo esc_url( $href ); ?>" class="callout-button button button-arrow <?php echo esc_attr( $button_classes ); ?>"<?php echo $button_style; ?><?php echo $popup_data; ?>><?php echo esc_html( $buttonText ); ?></a>
        </p>
    <?php endif; ?>
</aside>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_content_upgrade(array $attributes, string $content = ''): string
{
    ob_start();
    $title = stackless_theme_blocks_field( $attributes, 'title' );
    $text = stackless_theme_blocks_field( $attributes, 'text' );
    $url = stackless_theme_blocks_field( $attributes, 'url' );
    $icon = stackless_theme_blocks_field( $attributes, 'icon' );
    $buttonText = stackless_theme_blocks_field( $attributes, 'buttonText' );
    $bgColor = stackless_theme_blocks_field( $attributes, 'bgColor' );
    $bgColorClass = stackless_theme_blocks_field( $attributes, 'bgColorClass' );
    $bgImage = stackless_theme_blocks_field( $attributes, 'bgImage' );
    $textColor = stackless_theme_blocks_field( $attributes, 'textColor' );
    $textColorClass = stackless_theme_blocks_field( $attributes, 'textColorClass' );
    $buttonColor = stackless_theme_blocks_field( $attributes, 'buttonColor' );
    $buttonColorClass = stackless_theme_blocks_field( $attributes, 'buttonColorClass' );
    $buttonTextColor = stackless_theme_blocks_field( $attributes, 'buttonTextColor' );
    $buttonTextColorClass = stackless_theme_blocks_field( $attributes, 'buttonTextColorClass' );
    $popup = stackless_theme_blocks_field( $attributes, 'popup' );
    $has_popup = ! empty( $popup ) && empty( $url ) ? true : false;
    $popup_data = $has_popup ? stackless_theme_blocks_popup_trigger( (string) $popup, 'content-upgrade' ) : '';
    $has_popup = $popup_data !== '';
    $align = stackless_theme_blocks_field( $attributes, 'align' );
    $alignment = stackless_theme_blocks_field( $attributes, 'alignment' );
    $marginBottom = stackless_theme_blocks_field( $attributes, 'marginBottom' );
    $padding = stackless_theme_blocks_field( $attributes, 'padding' );
    $boxLeftRight = stackless_theme_blocks_field( $attributes, 'boxLeftRight' );
    $shadow = stackless_theme_blocks_field( $attributes, 'shadow' );
    $className = stackless_theme_blocks_field( $attributes, 'className' );
    $href = ! empty( $url ) ? $url : '#';
    $button_text = ! empty( $buttonText ) ? $buttonText : __( 'Download now', 'stackless-theme' );
    $eff_btn_bg = ! empty( $buttonColor ) ? $buttonColor : ( empty( $buttonColorClass ) ? '#2563eb' : '' );
    $eff_btn_text = ! empty( $buttonTextColor ) ? $buttonTextColor : ( empty( $buttonTextColorClass ) ? '#ffffff' : '' );
    $button_bg = $eff_btn_bg ? 'background-color: ' . esc_attr( $eff_btn_bg ) . ';' : '';
    $button_color = $eff_btn_text ? ' color: ' . esc_attr( $eff_btn_text ) . ';' : '';
    $button_style = $button_bg || $button_color ? ' style="' . $button_bg . $button_color . '"' : '';
    $eff_bg = ! empty( $bgColor ) ? $bgColor : ( empty( $bgColorClass ) ? '#1e293b' : '' );
    $eff_text = ! empty( $textColor ) ? $textColor : ( empty( $textColorClass ) ? '#ffffff' : '' );
    $bg_color = $eff_bg ? 'background-color: ' . esc_attr( $eff_bg ) . '; ' : '';
    $bg_image = ! empty( $bgImage ) ? ' background-image: url(\'' . esc_url( $bgImage ) . '\');' : '';
    $text_style = $eff_text ? ' color: ' . esc_attr( $eff_text ) . ';' : '';
    $style = $bg_color || $bg_image || $text_style ? ' style="' . $bg_color . $bg_image . $text_style . '"' : '';
    $classes = $button_classes = array();
    $classes[] = 'content-upgrade';
    $classes[] = ! empty( $align ) ? "align{$align}" : '';
    $classes[] = ! empty( $bgColorClass ) ? $bgColorClass : '';
    $classes[] = ! empty( $textColorClass ) ? $textColorClass : '';
    $classes[] = ! empty( $marginBottom ) ? $marginBottom : 'mb-mid';
    $classes[] = ! empty( $padding ) ? $padding : 'block-single';
    if ( ! empty( $className ) )
        $classes[] = $className;
    if ( ! empty( $boxLeftRight ) )
        $classes[] = 'box-lr';
    if ( ! empty( $alignment ) )
        $classes[] = 'text-' . esc_attr( $alignment );
    if ( ! empty( $shadow ) )
        $classes[] = 'shadow';
    if ( ! empty( $bgImage ) )
        $classes[] = 'image-overlay';
    $button_classes[] = ! empty( $buttonColorClass ) ? $buttonColorClass : '';
    $button_classes[] = ! empty( $buttonTextColorClass ) ? $buttonTextColorClass : '';
    if ( $has_popup )
        $button_classes[] = 'stb-popup-trigger';
    $classes = join( ' ', $classes );
    $button_classes = join( ' ', $button_classes );

    // Generate unique ID for accessibility
    $block_id = 'content-upgrade-' . wp_unique_id();
    $title_id = $block_id . '-title';
    $desc_id = $block_id . '-desc';
    $has_title = ! empty( $title );
    $has_text = ! empty( $text );
    $labelledby = $has_title ? $title_id : '';
    $describedby = $has_text ? $desc_id : '';
?>
<aside class="<?php echo esc_attr( $classes ); ?>" role="complementary"<?php echo $style; ?><?php echo $has_title ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : ''; ?>>
    <?php if ( ! empty( $icon ) ) : ?>
        <div class="content-upgrade-icon large-title mr-single" aria-hidden="true">
            <i class="<?php echo esc_attr( $icon ); ?>"></i>
        </div>
    <?php endif; ?>
    <?php if ( $has_title || $has_text ) : ?>
        <div class="content-upgrade-text mb-single">
            <?php if ( $has_title ) : ?>
                <p id="<?php echo esc_attr( $title_id ); ?>" class="small-title mb-small"><?php echo stackless_theme_blocks_text( $title ); ?></p>
            <?php endif; ?>
            <?php if ( $has_text ) : ?>
                <div id="<?php echo esc_attr( $desc_id ); ?>"><?php echo wp_kses_post( wpautop( $text ) ); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php
    // A popup only opens when a popup module answers the trigger filter; without
    // one there is nothing to trigger, so no button prints.
    if ( ! empty( $url ) || $has_popup ) : ?>
        <div class="content-upgrade-action">
            <a href="<?php echo esc_url( $href ); ?>" class="button button-arrow <?php echo esc_attr( $button_classes ); ?>"<?php echo $button_style; ?><?php echo $popup_data; ?><?php echo $has_popup ? ' role="button"' : ''; ?><?php echo $describedby ? ' aria-describedby="' . esc_attr( $desc_id ) . '"' : ''; ?>><?php echo esc_html( $button_text ); ?></a>
        </div>
    <?php endif; ?>
</aside>
    <?php
    return (string) ob_get_clean();
}

/**
 * Hands a FAQ's ID and settings to its items before they render. WordPress renders inner blocks
 * first, so by the time the FAQ's own callback runs every item has already printed.
 */
add_filter('pre_render_block', 'stackless_theme_blocks_faq_context', 10, 2);
function stackless_theme_blocks_faq_context($pre_render, $parsed_block)
{
    if (isset($parsed_block['blockName']) && $parsed_block['blockName'] === 'stackless/faq') {
        $attrs = isset($parsed_block['attrs']) && is_array($parsed_block['attrs']) ? $parsed_block['attrs'] : [];
        stackless_theme_blocks_faq_state([
            'id' => 'faq-' . stackless_theme_blocks_faq_count(),
            'item' => 0,
            'first_open' => !isset($attrs['firstOpen']) || $attrs['firstOpen'],
        ]);
    }
    return $pre_render;
}

/** The next FAQ number on this page. */
function stackless_theme_blocks_faq_count(): int
{
    static $count = 0;
    return ++$count;
}

/** The FAQ being rendered: pass an array to set it, null to read it, false to clear it. */
function stackless_theme_blocks_faq_state($set = null): array
{
    static $state = [];
    if (is_array($set)) {
        $state = $set;
    } elseif ($set === false) {
        $state = [];
    }
    return $state;
}

function stackless_theme_blocks_render_faq(array $attributes, string $content = ''): string
{
    // Set by stackless_theme_blocks_faq_context() before the items rendered. The fallback covers
    // a callback invoked outside render_block().
    $state = stackless_theme_blocks_faq_state();
    $id = !empty($state['id']) ? $state['id'] : 'faq-' . stackless_theme_blocks_faq_count();
    stackless_theme_blocks_faq_state(false);

    $multi_attr = !empty($attributes['multiOpen']) ? ' data-accordion-multi' : '';
    $margin = !empty($attributes['marginBottom']) ? $attributes['marginBottom'] : 'mb-double';

    $html = '<div id="' . esc_attr($id) . '" class="faq-accordion accordion ' . esc_attr($margin) . '"' . $multi_attr . ' itemscope itemtype="https://schema.org/FAQPage">' . $content . '</div>';

    return isset($attributes['schemaEnabled']) && !$attributes['schemaEnabled'] ? stackless_theme_blocks_strip_microdata($html) : $html;
}

function stackless_theme_blocks_render_faq_item(array $attributes, string $content = ''): string
{
    // Item counter scoped to the current FAQ parent.
    $state = stackless_theme_blocks_faq_state();
    $item_num = (int) ($state['item'] ?? 0) + 1;
    if ($state) {
        $state['item'] = $item_num;
        stackless_theme_blocks_faq_state($state);
    }

    $question = isset($attributes['question']) ? $attributes['question'] : '';
    $answer = isset($attributes['answer']) ? $attributes['answer'] : '';
    $parent_id = !empty($state['id']) ? $state['id'] : 'faq-1';
    $id = $parent_id . '_' . $item_num;
    $answer_id = $id . '_answer';
    $button_id = $id . '_question';
    $first_open = !empty($state['first_open']) && $item_num === 1;

    /*
     * The APG accordion pattern: a real <button> inside an element with heading semantics.
     * role="heading" rather than an <h3>, so questions do not land in the table of contents or
     * pick up heading typography. The answer starts hidden unless open; assets/js/blocks/faq.js
     * keeps it, aria-expanded and the active class in step.
     */
    ob_start();
    ?>
    <div id="<?php echo esc_attr($id); ?>" class="faq-item accordion-group<?php echo $first_open ? ' active' : ''; ?>" itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">
        <div class="accordion-heading" role="heading" aria-level="3"><button type="button" id="<?php echo esc_attr($button_id); ?>" class="accordion-title" itemprop="name" aria-expanded="<?php echo $first_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr($answer_id); ?>" data-accordion="<?php echo esc_attr((string) $item_num); ?>"><?php echo stackless_theme_blocks_text($question); ?></button></div>
        <div id="<?php echo esc_attr($answer_id); ?>" class="accordion-content" role="region" aria-labelledby="<?php echo esc_attr($button_id); ?>"<?php echo $first_open ? '' : ' hidden'; ?> itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
            <div itemprop="text"><?php echo wp_kses_post(wpautop($answer)); ?></div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_steps(array $attributes, string $content = ''): string
{

    return '<div class="stb-steps mb-double">' . $content . '</div>';
}

function stackless_theme_blocks_render_step(array $attributes, string $content = ''): string
{
    $number = isset( $attributes['number'] ) ? $attributes['number'] : '';
    $title = isset( $attributes['title'] ) ? $attributes['title'] : '';
    $text = isset( $attributes['text'] ) ? $attributes['text'] : '';

    ob_start();
    ?>
    <div class="stb-step">
        <div class="stb-step-number"><?php echo esc_html( $number ); ?></div>
        <div class="stb-step-content">
            <?php if ( $title ) : ?>
                <h4 class="stb-step-title"><?php echo stackless_theme_blocks_text( $title ); ?></h4>
            <?php endif; ?>
            <?php if ( $text ) : ?>
                <p class="stb-step-text"><?php echo stackless_theme_blocks_text( $text ); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_side_promotion(array $attributes, string $content = ''): string
{

    $position = isset( $attributes['position'] ) ? $attributes['position'] : 'right';
    $width = isset( $attributes['width'] ) ? intval( $attributes['width'] ) : 200;

    $classes = array( 'stb-side-promotion' );
    $classes[] = 'stb-side-promotion-' . esc_attr( $position );

    ob_start();
    ?>
    <div class="<?php echo implode( ' ', $classes ); ?>" style="width: <?php echo $width; ?>px;">
        <?php echo $content; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_side_promotion_item(array $attributes, string $content = ''): string
{
    $image = isset( $attributes['image'] ) ? $attributes['image'] : '';
    $title = isset( $attributes['title'] ) ? $attributes['title'] : '';
    $url = isset( $attributes['url'] ) ? $attributes['url'] : '';
    $newTab = isset( $attributes['newTab'] ) && $attributes['newTab'];

    if ( empty( $image ) && empty( $url ) ) {
        return '';
    }

    $target = $newTab ? ' target="_blank" rel="noopener"' : '';

    ob_start();
    ?>
    <a href="<?php echo esc_url( $url ); ?>" class="side-promo-panel"<?php echo $target; ?>>
        <?php if ( $image ) : ?>
            <?php echo stackless_theme_blocks_image( $image, 'large', array( 'alt' => $title, 'loading' => 'lazy' ) ); ?>
        <?php endif; ?>
        <?php if ( $title ) : ?>
            <span class="side-promo-title"><?php echo esc_html( $title ); ?></span>
        <?php endif; ?>
    </a>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_key_takeaway(array $attributes, string $content = ''): string
{

    $label = isset( $attributes['label'] ) ? $attributes['label'] : __( 'Key Takeaway', 'stackless-theme' );
    $text = isset( $attributes['content'] ) ? $attributes['content'] : '';
    $style = isset( $attributes['blockStyle'] ) ? $attributes['blockStyle'] : ( isset( $attributes['style'] ) && is_string( $attributes['style'] ) ? $attributes['style'] : 'highlight' );
    $icon = isset( $attributes['icon'] ) ? $attributes['icon'] : 'bolt';
    $bgColor = isset( $attributes['bgColor'] ) ? $attributes['bgColor'] : '';
    $borderColor = isset( $attributes['borderColor'] ) ? $attributes['borderColor'] : '';
    $textColor = isset( $attributes['textColor'] ) ? $attributes['textColor'] : '';
    $iconColor = isset( $attributes['iconColor'] ) ? $attributes['iconColor'] : '';
    $marginBottom = isset( $attributes['marginBottom'] ) ? $attributes['marginBottom'] : 'mb-double';

    if ( empty( $text ) ) {
        return '';
    }

    // Map icon values to MD icon font classes
    $icon_map = array(
        'bolt'    => 'stb-icon-bolt',
        'fire'    => 'stb-icon-fire',
        'star'    => 'stb-icon-star',
        'info'    => 'stb-icon-info',
        'warning' => 'stb-icon-warning',
        'ok'      => 'stb-icon-ok',
        'gift'    => 'stb-icon-gift',
        'trophy'  => 'stb-icon-trophy',
        'rocket'  => 'stb-icon-rocket',
        'heart'   => 'stb-icon-heart',
        'bell'    => 'stb-icon-bell',
        'gem'     => 'stb-icon-gem',
        // Legacy mappings for backwards compatibility
        'lightbulb'   => 'stb-icon-bolt',
        'star-filled' => 'stb-icon-star',
        'yes-alt'     => 'stb-icon-ok'
    );
    $icon_class = isset( $icon_map[ $icon ] ) ? $icon_map[ $icon ] : '';

    $classes = array( 'stb-key-takeaway', 'stb-key-takeaway-' . esc_attr( $style ), esc_attr( $marginBottom ) );

    // `has-shadow` was emitted here but no rule ever defined it, for this
    // block or globally — the "Add shadow" checkbox has been removed rather
    // than given a shadow this callout does not need. The attribute stays
    // declared so saved posts keep validating.
    $wrapper_styles = array();
    if ( $bgColor ) $wrapper_styles[] = 'background:' . esc_attr( $bgColor );
    // Custom accent drives the tint, hairline and icon together. Previously
    // this set `border-left-color` on a rail that no longer exists.
    if ( $borderColor ) $wrapper_styles[] = '--kt-accent:' . esc_attr( $borderColor );
    if ( $textColor ) $wrapper_styles[] = 'color:' . esc_attr( $textColor );

    ob_start();
    ?>
    <div class="<?php echo implode( ' ', $classes ); ?>"<?php echo $wrapper_styles ? ' style="' . implode( ';', $wrapper_styles ) . '"' : ''; ?>>
        <div class="stb-key-takeaway-header">
            <?php if ( $icon !== 'none' && $icon_class ) : ?>
                <span class="stb-key-takeaway-icon <?php echo esc_attr( $icon_class ); ?>"<?php echo $iconColor ? ' style="color:' . esc_attr( $iconColor ) . '"' : ''; ?>></span>
            <?php endif; ?>
            <span class="stb-key-takeaway-label"><?php echo esc_html( $label ); ?></span>
        </div>
        <div class="stb-key-takeaway-content">
            <?php echo wp_kses_post( $text ); ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_pull_quote(array $attributes, string $content = ''): string
{

    $quote = isset( $attributes['quote'] ) ? $attributes['quote'] : '';
    $citation = isset( $attributes['citation'] ) ? $attributes['citation'] : '';
    $style = isset( $attributes['quoteStyle'] ) ? $attributes['quoteStyle'] : ( isset( $attributes['style'] ) && is_string( $attributes['style'] ) ? $attributes['style'] : 'border' );
    $alignment = isset( $attributes['alignment'] ) ? $attributes['alignment'] : 'left';
    $blockWidth = isset( $attributes['blockWidth'] ) ? $attributes['blockWidth'] : '';
    $quoteColor = isset( $attributes['quoteColor'] ) ? $attributes['quoteColor'] : '';
    $citationColor = isset( $attributes['citationColor'] ) ? $attributes['citationColor'] : '';
    $borderColor = isset( $attributes['borderColor'] ) ? $attributes['borderColor'] : '';
    $bgColor = isset( $attributes['bgColor'] ) ? $attributes['bgColor'] : '';
    $fontSize = isset( $attributes['fontSize'] ) ? intval( $attributes['fontSize'] ) : 135;
    $marginBottom = isset( $attributes['marginBottom'] ) ? $attributes['marginBottom'] : 'mb-double';
    $shadow = isset( $attributes['shadow'] ) && $attributes['shadow'];

    if ( empty( $quote ) ) {
        return '';
    }

    $classes = array( 'stb-pull-quote', 'stb-pull-quote-' . esc_attr( $style ), esc_attr( $marginBottom ) );
    if ( $shadow ) $classes[] = 'has-shadow';

    // Add block width alignment class
    if ( $blockWidth ) {
        $classes[] = esc_attr( $blockWidth );
    }

    $wrapper_styles = array( 'text-align:' . esc_attr( $alignment ) );
    if ( $style === 'boxed' && $bgColor ) $wrapper_styles[] = 'background:' . esc_attr( $bgColor );
    if ( $style === 'dark' && $bgColor ) $wrapper_styles[] = 'background:' . esc_attr( $bgColor );
    if ( $borderColor ) {
        if ( $style === 'border' || $style === 'sidebar' ) {
            $wrapper_styles[] = 'border-left-color:' . esc_attr( $borderColor );
        } elseif ( $style === 'boxed' ) {
            $wrapper_styles[] = 'border-color:' . esc_attr( $borderColor );
        }
    }

    $quote_styles = array();
    if ( $fontSize !== 135 ) $quote_styles[] = 'font-size:' . ( $fontSize / 100 ) . 'em';
    if ( $quoteColor ) $quote_styles[] = 'color:' . esc_attr( $quoteColor );

    ob_start();

    if ( $style === 'bezel' ) : ?>
    <figure class="<?php echo implode( ' ', $classes ); ?>" style="<?php echo implode( ';', $wrapper_styles ); ?>" role="figure" itemscope itemtype="https://schema.org/Quotation">
        <div class="stb-pull-quote-bezel-inner">
            <span class="stb-pull-quote-bezel-mark" aria-hidden="true">&ldquo;</span>
            <blockquote class="stb-pull-quote-text"<?php echo $quote_styles ? ' style="' . implode( ';', $quote_styles ) . '"' : ''; ?>>
                <?php echo wp_kses_post( $quote ); ?>
            </blockquote>
            <?php if ( $citation ) : ?>
                <figcaption class="stb-pull-quote-citation"<?php echo $citationColor ? ' style="color:' . esc_attr( $citationColor ) . '"' : ''; ?>><cite><?php echo esc_html( $citation ); ?></cite></figcaption>
            <?php endif; ?>
        </div>
    </figure>
    <?php else : ?>
    <figure class="<?php echo implode( ' ', $classes ); ?>" style="<?php echo implode( ';', $wrapper_styles ); ?>" role="figure" itemscope itemtype="https://schema.org/Quotation">
        <?php if ( $style === 'quote-marks' ) : ?>
            <span class="stb-pull-quote-mark" aria-hidden="true"<?php echo $borderColor ? ' style="color:' . esc_attr( $borderColor ) . '"' : ''; ?>>"</span>
        <?php endif; ?>
        <blockquote class="stb-pull-quote-text"<?php echo $quote_styles ? ' style="' . implode( ';', $quote_styles ) . '"' : ''; ?>>
            <?php echo wp_kses_post( $quote ); ?>
        </blockquote>
        <?php if ( $citation ) : ?>
            <figcaption class="stb-pull-quote-citation"<?php echo $citationColor ? ' style="color:' . esc_attr( $citationColor ) . '"' : ''; ?>><cite><?php echo esc_html( $citation ); ?></cite></figcaption>
        <?php endif; ?>
    </figure>
    <?php endif;

    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_quote(array $attributes, string $content = ''): string
{

    $quote       = isset( $attributes['quote'] ) ? $attributes['quote'] : '';
    $citation    = isset( $attributes['citation'] ) ? $attributes['citation'] : '';
    $role        = isset( $attributes['role'] ) ? $attributes['role'] : '';
    $avatarUrl   = isset( $attributes['avatarUrl'] ) ? $attributes['avatarUrl'] : '';
    $rating      = isset( $attributes['rating'] ) ? intval( $attributes['rating'] ) : 0;
    $itemReviewed = isset( $attributes['itemReviewed'] ) ? trim( wp_strip_all_tags( $attributes['itemReviewed'] ) ) : '';
    // Support legacy 'style' attribute name (renamed to 'quoteStyle' to avoid WordPress reserved attribute conflict)
    $style       = isset( $attributes['quoteStyle'] ) ? $attributes['quoteStyle'] : ( isset( $attributes['style'] ) && is_string( $attributes['style'] ) ? $attributes['style'] : 'classic-centered' );
    $fontFamily  = isset( $attributes['fontFamily'] ) ? $attributes['fontFamily'] : 'sans';
    $fontSize    = isset( $attributes['fontSize'] ) ? intval( $attributes['fontSize'] ) : 125;
    $quoteColor  = isset( $attributes['quoteColor'] ) ? $attributes['quoteColor'] : '';
    $citationColor = isset( $attributes['citationColor'] ) ? $attributes['citationColor'] : '';
    $bgColor     = isset( $attributes['bgColor'] ) ? $attributes['bgColor'] : '';
    $borderColor = isset( $attributes['borderColor'] ) ? $attributes['borderColor'] : '';
    $marginBottom = isset( $attributes['marginBottom'] ) ? $attributes['marginBottom'] : 'mb-double';
    $shadow      = isset( $attributes['shadow'] ) && $attributes['shadow'];

    if ( empty( $quote ) ) {
        return '';
    }

    // Testimonial styles use Review schema, but only when both author
    // (citation) and itemReviewed are provided — Google requires both for
    // Review rich results. Otherwise fall back to Quotation, which has no
    // required fields and won't trigger Search Console warnings.
    $testimonial_styles = array( 'classic-centered', 'card-stars', 'dark-statement', 'asymmetric-mark' );
    $item_type = $attributes['itemType'] ?? '';
    $is_testimonial = in_array( $style, $testimonial_styles ) && '' !== trim( $citation ) && '' !== trim( $itemReviewed ) && in_array( $item_type, array( 'Product', 'Book', 'Course' ), true ) && $rating >= 1 && $rating <= 5;
    $schema_type = $is_testimonial ? 'https://schema.org/Review' : 'https://schema.org/Quotation';

    $classes = array( 'stb-quote', 'stb-quote-' . esc_attr( $style ), 'stb-quote-font-' . esc_attr( $fontFamily ), esc_attr( $marginBottom ) );
    if ( $shadow ) $classes[] = 'has-shadow';

    $wrapper_styles = array();
    if ( $bgColor ) $wrapper_styles[] = 'background-color:' . esc_attr( $bgColor );
    if ( $borderColor ) {
        if ( in_array( $style, array( 'vertical-rule', 'boxed-accent', 'sidebar' ) ) ) {
            $wrapper_styles[] = 'border-color:' . esc_attr( $borderColor );
        } elseif ( $style === 'card-stars' ) {
            $wrapper_styles[] = 'border-color:' . esc_attr( $borderColor );
        }
    }

    $quote_styles = array();
    if ( $fontSize !== 125 ) $quote_styles[] = 'font-size:' . ( $fontSize / 100 ) . 'em';
    if ( $quoteColor ) $quote_styles[] = 'color:' . esc_attr( $quoteColor );

    $cite_styles = array();
    if ( $citationColor ) $cite_styles[] = 'color:' . esc_attr( $citationColor );

    // Star rating SVG
    $stars_html = '';
    if ( $rating > 0 ) {
        $star_filled = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        $star_empty = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        $stars_html = '<div class="stb-quote-stars" role="img" aria-label="' . sprintf( esc_attr__( '%d out of 5 stars', 'stackless-theme' ), $rating ) . '">';
        for ( $i = 1; $i <= 5; $i++ ) {
            $stars_html .= $i <= $rating ? $star_filled : $star_empty;
        }
        $stars_html .= '</div>';
    }

    // Avatar
    $avatar_html = '';
    if ( $avatarUrl ) {
        $avatar_html = '<img class="stb-quote-avatar" src="' . esc_url( $avatarUrl ) . '" alt="' . esc_attr( $citation ) . '" loading="lazy" width="56" height="56" />';
    }

    // Meta block (name + role)
    $meta_html = '';
    if ( $citation || $role ) {
        $meta_html = '<figcaption class="stb-quote-meta">';
        if ( $avatarUrl && $style !== 'classic-centered' && $is_testimonial ) {
            $meta_html .= $avatar_html;
            $avatar_html = ''; // consumed
        }
        $meta_html .= '<div>';
        if ( $citation ) {
            $meta_html .= '<cite class="stb-quote-name"' . ( $cite_styles ? ' style="' . implode( ';', $cite_styles ) . '"' : '' ) . '>' . esc_html( $citation ) . '</cite>';
        }
        if ( $role ) {
            $meta_html .= '<span class="stb-quote-role">' . esc_html( $role ) . '</span>';
        }
        $meta_html .= '</div></figcaption>';
    }

    ob_start();
    ?>
    <figure class="<?php echo implode( ' ', $classes ); ?>"<?php echo $wrapper_styles ? ' style="' . implode( ';', $wrapper_styles ) . '"' : ''; ?> itemscope itemtype="<?php echo esc_attr( $schema_type ); ?>">
        <?php if ( $style === 'asymmetric-mark' ) : ?>
            <span class="stb-quote-mark" aria-hidden="true">&ldquo;</span>
            <div>
        <?php endif; ?>

        <?php if ( $style === 'oversize-mark' ) : ?>
            <span class="stb-quote-mark" aria-hidden="true">&ldquo;</span>
        <?php endif; ?>

        <?php if ( $style === 'classic-centered' && $avatar_html ) : ?>
            <?php echo $avatar_html; ?>
        <?php endif; ?>

        <?php if ( $style === 'card-stars' && $stars_html ) : ?>
            <?php echo $stars_html; ?>
        <?php endif; ?>

        <blockquote class="stb-quote-text"<?php echo $quote_styles ? ' style="' . implode( ';', $quote_styles ) . '"' : ''; ?> itemprop="<?php echo $is_testimonial ? 'reviewBody' : 'text'; ?>">
            <?php echo wp_kses_post( $quote ); ?>
        </blockquote>

        <?php echo $meta_html; ?>

        <?php if ( $style === 'asymmetric-mark' ) : ?>
            </div>
        <?php endif; ?>

        <?php if ( $is_testimonial && $rating > 0 ) : ?>
            <span itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                <meta itemprop="ratingValue" content="<?php echo esc_attr( $rating ); ?>" />
                <meta itemprop="bestRating" content="5" />
            </span>
        <?php endif; ?>
        <?php if ( $citation ) : ?>
            <span itemprop="<?php echo $is_testimonial ? 'author' : 'creator'; ?>" itemscope itemtype="https://schema.org/Person">
                <meta itemprop="name" content="<?php echo esc_attr( $citation ); ?>" />
            </span>
        <?php endif; ?>
        <?php if ( $is_testimonial ) : ?>
            <span itemprop="itemReviewed" itemscope itemtype="https://schema.org/<?php echo esc_attr( $item_type ); ?>">
                <meta itemprop="name" content="<?php echo esc_attr( $itemReviewed ); ?>" />
            </span>
        <?php endif; ?>
    </figure>
    <?php

    $html = ob_get_clean();
    if ( $is_testimonial && 'Product' === $item_type ) {
        $schema = array( '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $itemReviewed, 'review' => array( '@type' => 'Review', 'reviewBody' => wp_strip_all_tags( $quote ), 'author' => array( '@type' => 'Person', 'name' => wp_strip_all_tags( $citation ) ), 'reviewRating' => array( '@type' => 'Rating', 'ratingValue' => $rating, 'bestRating' => 5 ) ) );
        return stackless_theme_blocks_strip_microdata( $html ) . '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) . '</script>';
    }
    return $html;
}

function stackless_theme_blocks_render_related_content(array $attributes, string $content = ''): string
{

    $label          = isset( $attributes['label'] ) ? $attributes['label'] : __( 'Recommended Reading', 'stackless-theme' );
    $ctaText        = isset( $attributes['ctaText'] ) ? $attributes['ctaText'] : __( 'Read More', 'stackless-theme' );
    $newTab         = isset( $attributes['newTab'] ) && $attributes['newTab'];
    $marginBottom   = isset( $attributes['marginBottom'] ) ? $attributes['marginBottom'] : 'mb-double';
    $shadow         = isset( $attributes['shadow'] ) && $attributes['shadow'];
    $showExcerpts   = ! isset( $attributes['showExcerpts'] ) || $attributes['showExcerpts'];
    $showCta        = ! isset( $attributes['showCta'] ) || $attributes['showCta'];

    // Layout: 'card' (legacy single, image+text row), 'spotlight' (single, hero),
    // 'grid' (multi, square-image cards), 'list' (multi, horizontal rows),
    // 'inline' (multi, pill-style links).
    $layout = isset( $attributes['layout'] ) ? $attributes['layout'] : '';

    // Legacy single-post style fallback
    $legacyStyle = isset( $attributes['cardStyle'] ) ? $attributes['cardStyle']
        : ( isset( $attributes['style'] ) && is_string( $attributes['style'] ) ? $attributes['style'] : 'card' );

    // Color customizations (legacy single-post)
    $labelBgColor   = isset( $attributes['labelBgColor'] ) ? $attributes['labelBgColor'] : '';
    $labelTextColor = isset( $attributes['labelTextColor'] ) ? $attributes['labelTextColor'] : '';
    $bgColor        = isset( $attributes['bgColor'] ) ? $attributes['bgColor'] : '';
    $borderColor    = isset( $attributes['borderColor'] ) ? $attributes['borderColor'] : '';
    $titleColor     = isset( $attributes['titleColor'] ) ? $attributes['titleColor'] : '';
    $descColor      = isset( $attributes['descColor'] ) ? $attributes['descColor'] : '';
    $ctaColor       = isset( $attributes['ctaColor'] ) ? $attributes['ctaColor'] : '';

    // Resolve posts array. New mode wins when `posts` has items; legacy
    // single-post attributes are still honored otherwise.
    $posts = array();
    if ( ! empty( $attributes['posts'] ) && is_array( $attributes['posts'] ) ) {
        foreach ( $attributes['posts'] as $p ) {
            if ( empty( $p['url'] ) && empty( $p['postId'] ) ) continue;
            $posts[] = array(
                'id'    => isset( $p['postId'] ) ? (int) $p['postId'] : 0,
                'title' => isset( $p['title'] ) ? (string) $p['title'] : '',
                'desc'  => isset( $p['description'] ) ? (string) $p['description'] : '',
                'url'   => isset( $p['url'] ) ? (string) $p['url'] : '',
                'image' => isset( $p['image'] ) ? (string) $p['image'] : '',
            );
        }
    }

    if ( empty( $posts ) ) {
        // Legacy single-post path — preserves all existing blocks unchanged.
        $title       = isset( $attributes['title'] ) ? $attributes['title'] : '';
        $url         = isset( $attributes['url'] ) ? $attributes['url'] : '';
        if ( empty( $title ) || empty( $url ) ) return '';
        $posts[] = array(
            'id'    => isset( $attributes['postId'] ) ? (int) $attributes['postId'] : 0,
            'title' => $title,
            'desc'  => isset( $attributes['description'] ) ? $attributes['description'] : '',
            'url'   => $url,
            'image' => isset( $attributes['image'] ) ? $attributes['image'] : '',
        );
        if ( empty( $layout ) ) $layout = $legacyStyle; // map old 'card'/'compact'/'minimal'
    } elseif ( empty( $layout ) ) {
        $layout = 'grid';
    }

    // Columns for grid mode (2 or 3).
    $columns = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : ( count( $posts ) >= 3 ? 3 : 2 );
    $columns = max( 1, min( 3, $columns ) );

    $root_classes = array(
        'stb-related-content',
        'stb-related-content--' . esc_attr( $layout ),
        esc_attr( $marginBottom ),
    );
    // Keep legacy modifier classes so older custom CSS keeps targeting.
    $root_classes[] = 'stb-related-content-' . esc_attr( $layout );
    if ( $shadow ) $root_classes[] = 'has-shadow';
    if ( $layout === 'grid' ) $root_classes[] = 'stb-related-content--cols-' . $columns;

    $wrapper_styles = array();
    if ( $bgColor )     $wrapper_styles[] = 'background-color:' . esc_attr( $bgColor );
    if ( $borderColor ) $wrapper_styles[] = 'border-color:' . esc_attr( $borderColor );

    $label_styles = array();
    if ( $labelBgColor )   $label_styles[] = 'background-color:' . esc_attr( $labelBgColor );
    if ( $labelTextColor ) $label_styles[] = 'color:' . esc_attr( $labelTextColor );

    $target_attr = $newTab ? ' target="_blank" rel="noopener"' : '';

    ob_start();
    ?>
    <div class="<?php echo implode( ' ', $root_classes ); ?>"<?php echo $wrapper_styles ? ' style="' . esc_attr( implode( ';', $wrapper_styles ) ) . '"' : ''; ?>>
        <?php if ( ! empty( $label ) ) : ?>
            <div class="stb-related-content-label caps small bold"<?php echo $label_styles ? ' style="' . esc_attr( implode( ';', $label_styles ) ) . '"' : ''; ?>><?php echo esc_html( $label ); ?></div>
        <?php endif; ?>

        <div class="stb-related-content-items <?php
            // Use theme utility classes for layout-level spacing/grid.
            echo $layout === 'grid' ? 'grid-' . $columns . ' gap-half' : '';
            echo $layout === 'list' ? 'flex-column gap-half' : '';
            echo $layout === 'inline' ? 'flex-row flex-wrap gap-half' : '';
        ?>">
            <?php foreach ( $posts as $p ) :
                $p_title = $p['title'];
                $p_desc  = $p['desc'];
                $p_url   = $p['url'];
                $p_image = $p['image'];
                $p_id    = $p['id'];

                // The stylesheet crops the thumbnail square.
                $thumb_html = '';
                if ( $p_id && has_post_thumbnail( $p_id ) ) {
                    $thumb_html = get_the_post_thumbnail( $p_id, (string) apply_filters( 'stackless_theme_blocks_related_image_size', 'medium_large' ), array(
                        'alt'     => esc_attr( $p_title ),
                        'loading' => 'lazy',
                        'class'   => 'stb-related-content-img'
                    ) );
                } elseif ( ! empty( $p_image ) ) {
                    $thumb_html = '<img class="stb-related-content-img" src="' . esc_url( $p_image ) . '" alt="' . esc_attr( $p_title ) . '" loading="lazy" />';
                }

                $show_image = $thumb_html && in_array( $layout, array( 'card', 'spotlight', 'grid', 'list' ), true );
                $show_desc  = $p_desc && $showExcerpts && $layout !== 'inline' && $layout !== 'minimal';
                $show_cta_item = $showCta && $layout !== 'inline' && $layout !== 'minimal';
            ?>
                <a href="<?php echo esc_url( $p_url ); ?>" class="stb-related-content-item stb-related-content-link"<?php echo $target_attr; ?>>
                    <?php if ( $show_image ) : ?>
                        <div class="stb-related-content-image">
                            <?php echo $thumb_html; ?>
                        </div>
                    <?php endif; ?>
                    <div class="stb-related-content-text block-half">
                        <span class="stb-related-content-title bold"<?php echo $titleColor ? ' style="color:' . esc_attr( $titleColor ) . '"' : ''; ?>><?php echo esc_html( $p_title ); ?></span>
                        <?php if ( $show_desc ) : ?>
                            <span class="stb-related-content-desc small text-sec"<?php echo $descColor ? ' style="color:' . esc_attr( $descColor ) . '"' : ''; ?>><?php echo esc_html( $p_desc ); ?></span>
                        <?php endif; ?>
                        <?php if ( $show_cta_item ) : ?>
                            <span class="stb-related-content-cta small bold"<?php echo $ctaColor ? ' style="color:' . esc_attr( $ctaColor ) . '"' : ''; ?>><?php echo esc_html( $ctaText ); ?> &rarr;</span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_share_notice(array $attributes, string $content = ''): string
{
    ob_start();
    $social = stackless_theme_blocks_field( $attributes, 'socialMedia' );
    if ( empty( $social ) )
        $social = 'twitter';
    $text = stackless_theme_blocks_field( $attributes, 'text' );
    $textencode = urlencode( wp_strip_all_tags( (string) $text ) );
    if ( empty( $text ) )
        $text = __( 'Enter share text here...', 'stackless-theme' );
    $url = (string) get_permalink();
    $urlencode = urlencode( $url );
    $image = (string) get_the_post_thumbnail_url();
    $imageencode = urlencode( $image );
    $boxStyle = stackless_theme_blocks_field( $attributes, 'boxStyle' );
    if ( empty( $boxStyle ) )
        $boxStyle = 'outline';
    $buttonStyle = stackless_theme_blocks_field( $attributes, 'buttonStyle' );
    $buttonText = stackless_theme_blocks_field( $attributes, 'buttonText' );
    $align = stackless_theme_blocks_field( $attributes, 'align' );
    $alignment = stackless_theme_blocks_field( $attributes, 'alignment' );
    $marginBottom = stackless_theme_blocks_field( $attributes, 'marginBottom' );
    $padding = stackless_theme_blocks_field( $attributes, 'padding' );
    $className = stackless_theme_blocks_field( $attributes, 'className' );
    $classes = $button_classes = array();
    $classes[] = 'share-notice';
    $classes[] = "share-notice-{$social}";
    $classes[] = "share-notice-{$boxStyle}";
    $classes[] = ! empty( $align ) ? "align{$align}" : '';
    $classes[] = ! empty( $marginBottom ) ? $marginBottom : 'mb-mid';
    $classes[] = ! empty( $padding ) ? $padding : 'block-mid';
    if ( ! empty( $alignment ) )
        $classes[] = "text-{$alignment}";
    if ( ! empty( $className ) )
        $classes[] = $className;
    $classes = join( ' ', $classes );
    $button_classes[] = 'button';
    if ( $buttonStyle == 'full' )
        $button_classes[] = 'button-full';
    elseif ( $buttonStyle == 'pill' )
        $button_classes[] = 'button-pill';
    else
        $button_classes[] = 'button-outline';
    if ( $boxStyle == 'full' )
        $button_classes[] = 'white';
    $button_classes = join( ' ', $button_classes );
    $share_url = '';
    $button_text = __( 'Share', 'stackless-theme' );
    if ( $social == 'twitter' || $social == 'x' ) {
        $username = get_the_author_meta( 'twitter' );
        $share_url = "https://x.com/intent/tweet?text={$textencode}" . ( ! empty( $username ) ? "&via={$username}" : '' ) . "&url={$urlencode}";
    }
    elseif ( $social == 'facebook' )
        $share_url = "https://www.facebook.com/sharer.php?u={$urlencode}&t={$textencode}";
    elseif ( $social == 'pinterest' )
        $share_url = "https://pinterest.com/pin/create/button/?url={$urlencode}&description={$textencode}&media={$imageencode}&is_video=false";
    elseif( $social == 'linkedin' )
        $share_url = "https://www.linkedin.com/sharing/share-offsite/?url={$urlencode}";
    elseif ( $social == 'reddit' )
        $share_url = "https://www.reddit.com/submit?url={$urlencode}&title={$textencode}";
    elseif ( $social == 'whatsapp' )
        $share_url = "https://api.whatsapp.com/send?text={$textencode}%20{$urlencode}";
    elseif ( $social == 'telegram' )
        $share_url = "https://t.me/share/url?url={$urlencode}&text={$textencode}";

    if ( empty( $buttonText ) ) {
        if ( empty( $social ) || $social == 'twitter' || $social == 'x' )
            $button_text = __( 'Tweet This', 'stackless-theme' );
        elseif ( $social == 'facebook' )
            $button_text = __( 'Post to Facebook', 'stackless-theme' );
        elseif ( $social == 'pinterest' )
            $button_text = __( 'Pin This', 'stackless-theme' );
        elseif ( $social == 'linkedin' )
            $button_text = __( 'Share on LinkedIn', 'stackless-theme' );
        elseif ( $social == 'reddit' )
            $button_text = __( 'Share on Reddit', 'stackless-theme' );
        elseif ( $social == 'whatsapp' )
            $button_text = __( 'Send via WhatsApp', 'stackless-theme' );
        elseif ( $social == 'telegram' )
            $button_text = __( 'Send via Telegram', 'stackless-theme' );
    }
    else
        $button_text = $buttonText;
?>
<div class="<?php echo esc_attr( $classes ); ?>">
    <p class="share-notice-text small-text"><?php echo stackless_theme_blocks_text( $text ); ?></p>
    <i class="share-notice-icon stb-icon-<?php echo esc_attr( $social ); ?>"></i>
    <p class="share-notice-button">
        <a href="<?php echo esc_url( $share_url ); ?>" class="<?php echo esc_attr( $button_classes ); ?>" target="_blank" data-share="true"><?php echo esc_html( $button_text ); ?></a>
    </p>
</div>
    <?php
    return (string) ob_get_clean();
}

function stackless_theme_blocks_render_section(array $attributes, string $content = ''): string
{
    $html_tag        = ! empty( $attributes['htmlTag'] ) ? $attributes['htmlTag'] : 'div';
    $custom_tag      = ! empty( $attributes['customTag'] ) ? $attributes['customTag'] : '';
    $css             = ! empty( $attributes['sectionCSS'] ) ? $attributes['sectionCSS'] : '';
    $anchor          = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : '';
    $class_name      = ! empty( $attributes['className'] ) ? $attributes['className'] : '';
    $align           = ! empty( $attributes['align'] ) ? 'align' . $attributes['align'] : '';
    $html_attributes = ! empty( $attributes['htmlAttributes'] ) ? $attributes['htmlAttributes'] : '';
    $aria_hidden     = ! empty( $attributes['ariaHidden'] );
    $aria_label      = ! empty( $attributes['ariaLabel'] ) ? $attributes['ariaLabel'] : '';
    $aria_labelled   = ! empty( $attributes['ariaLabelledBy'] ) ? $attributes['ariaLabelledBy'] : '';
    $role            = ! empty( $attributes['role'] ) ? $attributes['role'] : '';
    $custom_role     = ! empty( $attributes['customRole'] ) ? $attributes['customRole'] : '';
    $hidden          = ! empty( $attributes['hidden'] );

    // Determine tag
    $allowed_tags = array( 'div', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav', 'figure', 'details' );
    if ( $html_tag === 'custom' && $custom_tag ) {
        $tag = stackless_theme_blocks_section_tag( $custom_tag );
    } elseif ( in_array( $html_tag, $allowed_tags, true ) ) {
        $tag = $html_tag;
    } else {
        $tag = 'div';
    }

    // Build classes
    $classes = array_filter( array(
        'wp-block-stackless-section',
        $class_name,
        $align,
    ) );

    // Build attributes string
    $attrs = '';

    if ( $anchor ) {
        $attrs .= ' id="' . esc_attr( $anchor ) . '"';
    }

    $attrs .= ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';

    // Role attribute
    $resolved_role = ( $role === 'custom' && $custom_role ) ? $custom_role : $role;
    if ( $resolved_role ) {
        $attrs .= ' role="' . esc_attr( $resolved_role ) . '"';
    }

    // ARIA attributes
    if ( $aria_hidden ) {
        $attrs .= ' aria-hidden="true"';
    }
    if ( $aria_label ) {
        $attrs .= ' aria-label="' . esc_attr( $aria_label ) . '"';
    }
    if ( $aria_labelled ) {
        $attrs .= ' aria-labelledby="' . esc_attr( $aria_labelled ) . '"';
    }

    // Hidden attribute
    if ( $hidden ) {
        $attrs .= ' hidden';
    }

    // Custom HTML attributes (data-*, tabindex, style, etc.)
    if ( $html_attributes ) {
        $attrs .= ' ' . stackless_theme_blocks_safe_attributes( $html_attributes );
    }

    $output = '';

    if ( $css ) {
        $output .= '<style>' . wp_strip_all_tags( $css ) . '</style>';
    }

    $output .= '<' . $tag . $attrs . '>';
    $output .= $content;
    $output .= '</' . $tag . '>';

    return $output;
}

function stackless_theme_blocks_section_tag(string $custom_tag): string
{
    $tag = sanitize_key( $custom_tag );

    if ( '' === $tag )
        return 'div';

    $post       = get_post();
    $author_ok  = $post ? user_can( (int) $post->post_author, 'unfiltered_html' ) : current_user_can( 'unfiltered_html' );

    if ( $author_ok )
        return $tag;

    $safe = array(
        'div', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav', 'figure', 'figcaption',
        'details', 'summary', 'hgroup', 'search', 'address', 'blockquote', 'p', 'span', 'ul', 'ol', 'li',
        'dl', 'dt', 'dd', 'fieldset', 'legend', 'mark', 'small', 'strong', 'em', 'b', 'i', 'u', 's', 'q',
        'cite', 'time', 'abbr', 'pre', 'code', 'kbd', 'samp', 'var', 'sub', 'sup', 'ins', 'del', 'center',
    );

    if ( in_array( $tag, $safe, true ) )
        return $tag;

    // Valid custom element name: lowercase letter first, at least one
    // hyphen, and not one of the names the spec reserves.
    $reserved = array( 'annotation-xml', 'color-profile', 'font-face', 'font-face-src', 'font-face-uri', 'font-face-format', 'font-face-name', 'missing-glyph' );

    if ( preg_match( '/^[a-z][a-z0-9]*-[a-z0-9-]*$/', $tag ) && ! in_array( $tag, $reserved, true ) )
        return $tag;

    return 'div';
}

function stackless_theme_blocks_safe_attributes(string $attr_string): string
{
    $safe_prefixes = array( 'data-', 'aria-' );
    $safe_names    = array( 'tabindex', 'style', 'title', 'lang', 'dir', 'translate', 'contenteditable', 'draggable', 'spellcheck', 'inputmode', 'enterkeyhint', 'autofocus', 'loading', 'fetchpriority', 'decoding', 'rel', 'target', 'download', 'hreflang', 'type', 'value', 'name', 'for', 'form', 'method', 'action', 'width', 'height', 'viewbox', 'preserveaspectratio', 'xmlns', 'fill', 'stroke', 'opacity' );
    $output        = array();

    if ( preg_match_all( '/([\w-]+)="([^"]*)"/', $attr_string, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $match ) {
            $name  = strtolower( $match[1] );
            $value = $match[2];

            // Check against safe prefixes
            $is_safe = in_array( $name, $safe_names, true );
            if ( ! $is_safe ) {
                foreach ( $safe_prefixes as $prefix ) {
                    if ( strpos( $name, $prefix ) === 0 ) {
                        $is_safe = true;
                        break;
                    }
                }
            }

            if ( $is_safe ) {
                $output[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
            }
        }
    }

    return implode( ' ', $output );
}

function stackless_theme_blocks_render_spoiler(array $attributes, string $content = ''): string
{
    static $spoiler_id = 0;
    $spoiler_id++;
    $id = 'spoiler-' . $spoiler_id;

    $label = isset( $attributes['label'] ) ? $attributes['label'] : __( 'Click to reveal', 'stackless-theme' );
    $revealedLabel = isset( $attributes['revealedLabel'] ) ? $attributes['revealedLabel'] : __( 'Hide', 'stackless-theme' );
    $style = isset( $attributes['blockStyle'] ) ? $attributes['blockStyle'] : ( isset( $attributes['style'] ) && is_string( $attributes['style'] ) ? $attributes['style'] : 'default' );
    $bgColor = isset( $attributes['bgColor'] ) ? $attributes['bgColor'] : '';
    $textColor = isset( $attributes['textColor'] ) ? $attributes['textColor'] : '';
    $marginBottom = isset( $attributes['marginBottom'] ) ? $attributes['marginBottom'] : 'mb-double';

    $classes = array( 'stb-spoiler', 'stb-spoiler-' . esc_attr( $style ), esc_attr( $marginBottom ) );

    $wrapper_styles = array();
    if ( $bgColor ) $wrapper_styles[] = 'background-color:' . esc_attr( $bgColor );
    if ( $textColor ) $wrapper_styles[] = 'color:' . esc_attr( $textColor );

    ob_start();
    ?>
    <div id="<?php echo esc_attr( $id ); ?>" class="<?php echo implode( ' ', $classes ); ?>"<?php echo $wrapper_styles ? ' style="' . implode( ';', $wrapper_styles ) . '"' : ''; ?>>
        <button class="stb-spoiler-toggle" type="button" aria-expanded="false" data-label="<?php echo esc_attr( $label ); ?>" data-revealed-label="<?php echo esc_attr( $revealedLabel ); ?>">
            <span class="stb-spoiler-icon stb-icon-eye"></span>
            <span class="stb-spoiler-label"><?php echo esc_html( $label ); ?></span>
        </button>
        <div class="stb-spoiler-content" hidden>
            <?php echo $content; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

/**
 * The attributes that make a button open a popup, with a leading space, or '' when no popup
 * module is active. Callout and Content Upgrade accept the popup attribute and print nothing
 * for it on their own; Stackless Pro's Popups module answers this filter.
 */
function stackless_theme_blocks_popup_trigger(string $popup, string $slug): string
{
    return (string) apply_filters('stackless_theme_blocks_popup_trigger', '', $popup, $slug);
}

/**
 * Carries a legacy string "style" attribute over to the block's own attribute name.
 *
 * These blocks once stored their variant in "style", which block supports now claim as an
 * object. WordPress validates attributes against that schema before the render callback runs,
 * so an old string value would be dropped and the post would lose its variant.
 * render_block_data runs before that validation.
 */
add_filter('render_block_data', 'stackless_theme_blocks_legacy_style');
function stackless_theme_blocks_legacy_style($parsed_block)
{
    $renamed = [
        'stackless/key-takeaway' => 'blockStyle',
        'stackless/spoiler' => 'blockStyle',
        'stackless/share-notice' => 'blockStyle',
        'stackless/pull-quote' => 'quoteStyle',
        'stackless/quote' => 'quoteStyle',
        'stackless/related-content' => 'cardStyle',
        'stackless/plugin-card' => 'cardStyle',
        'stackless/theme-card' => 'cardStyle',
        'stackless/github-card' => 'cardStyle',
        'stackless/npm-card' => 'cardStyle',
        'stackless/book-card' => 'cardStyle',
        'stackless/youtube-card' => 'cardStyle',
        'stackless/podcast-card' => 'cardStyle',
    ];
    if (!is_array($parsed_block) || empty($parsed_block['blockName']) || !isset($renamed[$parsed_block['blockName']])
        || !isset($parsed_block['attrs']['style']) || !is_string($parsed_block['attrs']['style'])) {
        return $parsed_block;
    }
    $target = $renamed[$parsed_block['blockName']];
    // The current attribute wins if a block carries both.
    if (!isset($parsed_block['attrs'][$target]) && $parsed_block['attrs']['style'] !== '') {
        $parsed_block['attrs'][$target] = $parsed_block['attrs']['style'];
    }
    unset($parsed_block['attrs']['style']);
    return $parsed_block;
}
