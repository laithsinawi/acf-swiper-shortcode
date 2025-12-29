<?php
/**
 * Plugin Name: ACF Swiper Shortcode
 * Description: Simple Swiper slider powered by ACF repeater fields. No builder dependency.
 * Version: 0.0.5
 * Author: Sinawi Web Design
 */

namespace ACFSwiperShortcode;

if (!defined('ABSPATH')) {
    exit;
}

const VERSION = '0.0.5';

/**
 * Default settings.
 *
 * @return array
 */
function defaults(): array
{
    return [
        'data_source' => 'acf',
        'acf' => [
            'repeater' => 'slide',
            'heading' => 'slide_heading',
            'text' => 'slide_text',
            'button_text' => 'button_text',
            'button_link' => 'button_link',
            'image' => 'slide_background_image',
        ],
        'builtin' => [
            'slides' => [],
        ],
        'slider' => [
            'min_height' => 500,
            'gap' => 14,
            'content_max_width' => 1200,
            'content_padding' => 24,
            'slides_per_view' => 1,
            'space_between' => 24,
            'loop' => true,
            'speed' => 600,
            'autoplay' => true,
            'autoplay_delay' => 4000,
            'pagination' => true,
        ],
        'styles' => [
            'text_color' => '#ffffff',
            'button_bg' => '#b0192f',
            'button_text' => '#ffffff',
            'button_bg_hover' => '#8f1226',
            'button_text_hover' => '#ffffff',
            'button_radius' => 0,
            'dot_size' => 16,
            'dot_active' => '#ffffff',
            'dot_inactive' => 'rgba(255,255,255,0.45)',
            'overlay' => 'rgba(0,0,0,0.45)',
            'custom_css' => '',
        ],
    ];
}

/**
 * Get saved settings merged with defaults.
 *
 * @return array
 */
function get_settings(): array
{
    $saved = get_option('acfswiper_settings', []);
    return array_replace_recursive(defaults(), is_array($saved) ? $saved : []);
}

/**
 * Register assets.
 *
 * @return void
 */
function register_assets(): void
{
    wp_register_style(
        'acfswiper-swiper',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
        [],
        null
    );

    wp_register_style(
        'acfswiper',
        plugins_url('assets/css/swiper.css', __FILE__),
        ['acfswiper-swiper'],
        VERSION
    );

    wp_register_script(
        'acfswiper-swiper',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        [],
        null,
        true
    );

    wp_register_script(
        'acfswiper-init',
        plugins_url('assets/js/swiper-init.js', __FILE__),
        ['acfswiper-swiper'],
        VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\register_assets');

/**
 * Render a fallback block when requirements are missing.
 *
 * @param array  $atts
 * @param array  $settings
 * @param string $reason
 * @param array  $context Additional context (e.g., source).
 * @return string
 */
function render_fallback(array $atts, array $settings, string $reason, array $context = []): string
{
    // Ensure base styles are available so the placeholder looks intentional.
    wp_enqueue_style('acfswiper');

    // Show guidance to editors/admins so they can configure fields quickly.
    $is_admin = current_user_can('manage_options') || current_user_can('edit_posts');

    $source = $context['source'] ?? ($settings['data_source'] ?? 'acf');
    $repeater = $settings['acf']['repeater'] ?? 'slide';
    $fields = [
        ['label' => 'Heading', 'key' => $settings['acf']['heading'] ?? 'slide_heading'],
        ['label' => 'Text', 'key' => $settings['acf']['text'] ?? 'slide_text'],
        ['label' => 'Button text', 'key' => $settings['acf']['button_text'] ?? 'button_text'],
        ['label' => 'Button link', 'key' => $settings['acf']['button_link'] ?? 'button_link'],
        ['label' => 'Background image', 'key' => $settings['acf']['image'] ?? 'slide_background_image'],
    ];

    $style_min_height = (int) ($atts['min_height'] ?? $settings['slider']['min_height']);
    $style_text = esc_attr($atts['text_color'] ?? $settings['styles']['text_color']);
    $overlay = esc_attr($settings['styles']['overlay'] ?? 'rgba(0,0,0,0.35)');
    $custom_css = $settings['styles']['custom_css'] ?? '';

    if ($reason === 'missing_acf') {
        $message = 'ACF Pro needs to be active for this slider. Activate ACF Pro to replace this placeholder with your slides.';
    } elseif ($source === 'builtin' || $reason === 'no_slides_builtin') {
        $message = 'Add at least one slide in ACF Swiper settings under "Built-in Slides" to replace this placeholder.';
    } else {
        $message = 'Add at least one slide to the "' . esc_html($atts['field']) . '" repeater to replace this placeholder.';
    }

    ob_start();
    ?>
    <div class="acf-swiper acf-swiper--fallback" style="min-height: <?php echo $style_min_height; ?>px;">
        <div class="acf-swiper__fallback-inner">
            <div class="acf-swiper__fallback-badge">ACF Swiper</div>
            <h2 class="acf-swiper__fallback-title">Slides not ready yet</h2>
            <p class="acf-swiper__fallback-message"><?php echo esc_html($message); ?></p>
            <?php if ($is_admin && $source === 'acf') : ?>
                <div class="acf-swiper__fallback-admin">
                    <strong>ACF setup</strong>
                    <div>Repeater field: <span class="acf-swiper__code"><?php echo esc_html($repeater); ?></span></div>
                    <div>Sub-fields:</div>
                    <ul class="acf-swiper__fallback-list">
                        <?php foreach ($fields as $field) : ?>
                            <li><span class="acf-swiper__code"><?php echo esc_html($field['key']); ?></span> (<?php echo esc_html($field['label']); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                    <div>Shortcode: <span class="acf-swiper__code">[acf_swiper]</span></div>
                </div>
            <?php elseif ($is_admin && $source === 'builtin') : ?>
                <div class="acf-swiper__fallback-admin">
                    <strong>Built-in slides</strong>
                    <div>Manage slides in <span class="acf-swiper__code">Settings → ACF Swiper → Built-in Slides</span>.</div>
                    <div>Shortcode: <span class="acf-swiper__code">[acf_swiper]</span></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <style>
        .acf-swiper--fallback {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 32px;
            color: <?php echo $style_text; ?>;
            background:
                radial-gradient(circle at 20% 20%, rgba(255,255,255,0.08), transparent 35%),
                radial-gradient(circle at 80% 30%, rgba(255,255,255,0.04), transparent 30%),
                #0f172a;
            overflow: hidden;
        }
        .acf-swiper--fallback::after {
            content: '';
            position: absolute;
            inset: 0;
            background: <?php echo $overlay; ?>;
            opacity: 0.35;
            z-index: 0;
        }
        .acf-swiper__fallback-inner {
            position: relative;
            z-index: 1;
            max-width: 720px;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .acf-swiper__fallback-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: center;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .acf-swiper__fallback-title {
            margin: 0;
            font-size: 28px;
            line-height: 1.2;
        }
        .acf-swiper__fallback-message {
            margin: 0;
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.9;
        }
        .acf-swiper__fallback-admin {
            margin-top: 10px;
            padding: 12px 14px;
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 12px;
            text-align: left;
            background: rgba(255,255,255,0.04);
            font-size: 14px;
            line-height: 1.5;
        }
        .acf-swiper__fallback-list {
            margin: 6px 0 8px;
            padding-left: 18px;
        }
        .acf-swiper__code {
            display: inline-block;
            padding: 2px 6px;
            background: rgba(0,0,0,0.35);
            border-radius: 6px;
            font-family: ui-monospace, SFMono-Regular, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 12px;
        }
        <?php if (!empty($custom_css)) : echo $custom_css; endif; ?>
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode handler.
 *
 * @param array $atts
 * @return string
 */
function shortcode(array $atts = []): string
{
    $settings = get_settings();

    $atts = shortcode_atts(
        [
            'post_id' => get_the_ID(),
            'field' => $settings['acf']['repeater'],
            'source' => $settings['data_source'],
            'slides_per_view' => $settings['slider']['slides_per_view'],
            'space_between' => $settings['slider']['space_between'],
            'loop' => $settings['slider']['loop'] ? 'true' : 'false',
            'speed' => $settings['slider']['speed'],
            'autoplay' => $settings['slider']['autoplay'] ? 'true' : 'false',
            'autoplay_delay' => $settings['slider']['autoplay_delay'],
            'min_height' => $settings['slider']['min_height'],
            'gap' => $settings['slider']['gap'],
            'content_max_width' => $settings['slider']['content_max_width'],
            'content_padding' => $settings['slider']['content_padding'],
            'pagination' => $settings['slider']['pagination'] ? 'true' : 'false',
            'text_color' => $settings['styles']['text_color'],
            'button_bg' => $settings['styles']['button_bg'],
            'button_text' => $settings['styles']['button_text'],
            'button_bg_hover' => $settings['styles']['button_bg_hover'],
            'button_text_hover' => $settings['styles']['button_text_hover'],
            'button_radius' => $settings['styles']['button_radius'],
            'dot_size' => $settings['styles']['dot_size'],
            'dot_active' => $settings['styles']['dot_active'],
            'dot_inactive' => $settings['styles']['dot_inactive'],
            'overlay' => $settings['styles']['overlay'],
            'custom_css' => $settings['styles']['custom_css'],
        ],
        $atts,
        'acf_swiper'
    );

    $post_id = is_numeric($atts['post_id']) ? (int) $atts['post_id'] : get_the_ID();
    $field = sanitize_key($atts['field']);
    $atts['field'] = $field;
    $source = in_array($atts['source'], ['acf', 'builtin'], true) ? $atts['source'] : $settings['data_source'];
    $atts['source'] = $source;

    $style_text = esc_attr($atts['text_color']);
    $style_gap = (int) $atts['gap'];
    $style_min_height = (int) $atts['min_height'];
    $style_btn_bg = esc_attr($atts['button_bg']);
    $style_btn_text = esc_attr($atts['button_text']);
    $style_btn_bg_hover = esc_attr($atts['button_bg_hover']);
    $style_btn_text_hover = esc_attr($atts['button_text_hover']);
    $style_btn_radius = (int) $atts['button_radius'];
    $style_dot_size = (int) $atts['dot_size'];
    $style_overlay = esc_attr($atts['overlay']);
    $dot_active = esc_attr($atts['dot_active']);
    $dot_inactive = esc_attr($atts['dot_inactive']);
    $custom_css = $settings['styles']['custom_css'];
    $content_max_width = (int) $atts['content_max_width'];
    $content_padding = (int) $atts['content_padding'];

    // Built-in data source path.
    if ($source === 'builtin') {
        $slides = $settings['builtin']['slides'] ?? [];
        if (empty($slides)) {
            return render_fallback($atts, $settings, 'no_slides_builtin', ['source' => 'builtin']);
        }

        wp_enqueue_style('acfswiper-swiper');
        wp_enqueue_style('acfswiper');
        wp_enqueue_script('acfswiper-swiper');
        wp_enqueue_script('acfswiper-init');

        ob_start();
        ?>
        <div
            class="acf-swiper"
            data-acf-swiper
            data-slides-per-view="<?php echo esc_attr($atts['slides_per_view']); ?>"
            data-space-between="<?php echo esc_attr($atts['space_between']); ?>"
            data-loop="<?php echo esc_attr($atts['loop']); ?>"
            data-speed="<?php echo esc_attr($atts['speed']); ?>"
            data-autoplay="<?php echo esc_attr($atts['autoplay']); ?>"
            data-autoplay-delay="<?php echo esc_attr($atts['autoplay_delay']); ?>"
        >
            <div class="swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($slides as $slide) :
                        $heading = $slide['heading'] ?? '';
                        $text = $slide['text'] ?? '';
                        $btn_txt = $slide['button_text'] ?? '';
                        $btn = $slide['button_link'] ?? '';
                        $img = $slide['image'] ?? '';

                        $img_url = '';
                        if (is_numeric($img)) {
                            $img_url = wp_get_attachment_image_url((int) $img, 'full');
                        } elseif (is_string($img) && $img) {
                            $img_url = $img;
                        }
                        ?>
                        <div class="swiper-slide">
                            <?php if ($img_url) : ?>
                                <img class="slide-bg" src="<?php echo esc_url($img_url); ?>" alt="">
                            <?php endif; ?>

                            <div class="slide-overlay" style="background: <?php echo $style_overlay; ?>;"></div>

                            <div class="slide-inner" style="color: <?php echo $style_text; ?>; gap: <?php echo $style_gap; ?>px; align-items: flex-start; text-align: left;">
                                <div class="slide-inner__content" style="width: 100%; max-width: <?php echo $content_max_width; ?>px; margin: 0 auto; padding: 0 <?php echo $content_padding; ?>px; display: flex; flex-direction: column; gap: <?php echo $style_gap; ?>px;">
                                <?php if ($heading) : ?>
                                    <h1 style="color: <?php echo $style_text; ?>; margin: 0; line-height: 1.1;"><?php echo esc_html($heading); ?></h1>
                                <?php endif; ?>

                                <?php if ($text) : ?>
                                    <div class="slide-text" style="color: <?php echo $style_text; ?>; margin: 0;"><?php echo wp_kses_post(nl2br($text)); ?></div>
                                <?php endif; ?>

                                <?php if ($btn) : ?>
                                    <a
                                        class="slide-button"
                                        href="<?php echo esc_url($btn); ?>"
                                        style="
                                            display: inline-flex;
                                            align-items: center;
                                            justify-content: center;
                                            padding: 10px 18px;
                                            border-radius: <?php echo $style_btn_radius; ?>px;
                                            background: <?php echo $style_btn_bg; ?>;
                                            color: <?php echo $style_btn_text; ?>;
                                            text-decoration: none;
                                            transition: background 160ms ease, color 160ms ease;
                                            width: auto;
                                            max-width: max-content;
                                            box-sizing: border-box;
                                        "
                                        onmouseover="this.style.background='<?php echo $style_btn_bg_hover; ?>';this.style.color='<?php echo $style_btn_text_hover; ?>';"
                                        onmouseout="this.style.background='<?php echo $style_btn_bg; ?>';this.style.color='<?php echo $style_btn_text; ?>';"
                                    >
                                        <?php echo esc_html($btn_txt ?: 'Learn More'); ?>
                                    </a>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($atts['pagination'] === 'true') : ?>
                    <div class="swiper-pagination"></div>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .acf-swiper {
                min-height: <?php echo $style_min_height; ?>px;
            }
            .acf-swiper .swiper {
                min-height: inherit;
                height: auto;
            }
            .acf-swiper .swiper-wrapper,
            .acf-swiper .swiper-slide,
            .acf-swiper .slide-inner {
                min-height: inherit;
            }
            .acf-swiper .slide-bg {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
                z-index: 0;
            }
            .acf-swiper .slide-overlay {
                position: absolute;
                inset: 0;
                z-index: 1;
                pointer-events: none;
            }
            .acf-swiper .slide-inner {
                position: relative;
                z-index: 2;
                display: flex;
                flex-direction: column;
                gap: <?php echo $style_gap; ?>px;
                color: <?php echo $style_text; ?>;
            }
            .acf-swiper .swiper-pagination-bullet {
                background: <?php echo $dot_inactive; ?>;
                opacity: 1;
                transition: background 160ms ease, transform 160ms ease;
                width: <?php echo $style_dot_size; ?>px;
                height: <?php echo $style_dot_size; ?>px;
            }
            .acf-swiper .swiper-pagination-bullet-active {
                background: <?php echo $dot_active; ?>;
                transform: scale(1.1);
            }
            .acf-swiper .swiper-pagination {
                position: absolute;
                bottom: 16px;
                left: 0;
                right: 0;
                z-index: 3;
                display: flex;
                justify-content: center;
                gap: 10px;
            }
            <?php if (!empty($custom_css)) : echo $custom_css; endif; ?>
        </style>
        <?php
        return ob_get_clean();
    }

    if (!function_exists('have_rows')) {
        return render_fallback($atts, $settings, 'missing_acf', ['source' => 'acf']);
    }

    if (!have_rows($field, $post_id)) {
        return render_fallback($atts, $settings, 'no_slides', ['source' => 'acf']);
    }

    // Enqueue assets.
    wp_enqueue_style('acfswiper-swiper');
    wp_enqueue_style('acfswiper');
    wp_enqueue_script('acfswiper-swiper');
    wp_enqueue_script('acfswiper-init');

    ob_start();
    ?>
    <div
        class="acf-swiper"
        data-acf-swiper
        data-slides-per-view="<?php echo esc_attr($atts['slides_per_view']); ?>"
        data-space-between="<?php echo esc_attr($atts['space_between']); ?>"
        data-loop="<?php echo esc_attr($atts['loop']); ?>"
        data-speed="<?php echo esc_attr($atts['speed']); ?>"
        data-autoplay="<?php echo esc_attr($atts['autoplay']); ?>"
        data-autoplay-delay="<?php echo esc_attr($atts['autoplay_delay']); ?>"
    >
        <div class="swiper">
            <div class="swiper-wrapper">
                <?php
                while (have_rows($field, $post_id)) {
                    the_row();
                    $heading = get_sub_field($settings['acf']['heading']);
                    $text = get_sub_field($settings['acf']['text']);
                    $btn_txt = get_sub_field($settings['acf']['button_text']);
                    $btn = get_sub_field($settings['acf']['button_link']);
                    $img = get_sub_field($settings['acf']['image']);

                    $img_url = '';
                    if (is_array($img) && !empty($img['url'])) {
                        $img_url = $img['url'];
                    } elseif (is_numeric($img)) {
                        $img_url = wp_get_attachment_image_url((int) $img, 'full');
                    }
                    ?>
                    <div class="swiper-slide">
                        <?php if ($img_url) : ?>
                            <img class="slide-bg" src="<?php echo esc_url($img_url); ?>" alt="">
                        <?php endif; ?>

                        <div class="slide-overlay" style="background: <?php echo $style_overlay; ?>;"></div>

                        <div class="slide-inner" style="color: <?php echo $style_text; ?>; gap: <?php echo $style_gap; ?>px; align-items: flex-start; text-align: left;">
                            <div class="slide-inner__content" style="width: 100%; max-width: <?php echo $content_max_width; ?>px; margin: 0 auto; padding: 0 <?php echo $content_padding; ?>px; display: flex; flex-direction: column; gap: <?php echo $style_gap; ?>px;">
                            <?php if ($heading) : ?>
                                <h1 style="color: <?php echo $style_text; ?>; margin: 0; line-height: 1.1;"><?php echo esc_html($heading); ?></h1>
                            <?php endif; ?>

                            <?php if ($text) : ?>
                                <div class="slide-text" style="color: <?php echo $style_text; ?>; margin: 0;"><?php echo wp_kses_post(nl2br($text)); ?></div>
                            <?php endif; ?>

                            <?php if (is_array($btn) && !empty($btn['url'])) : ?>
                                <a
                                    class="slide-button"
                                    href="<?php echo esc_url($btn['url']); ?>"
                                    style="
                                        display: inline-flex;
                                        align-items: center;
                                        justify-content: center;
                                        padding: 10px 18px;
                                        border-radius: <?php echo $style_btn_radius; ?>px;
                                        background: <?php echo $style_btn_bg; ?>;
                                        color: <?php echo $style_btn_text; ?>;
                                        text-decoration: none;
                                        transition: background 160ms ease, color 160ms ease;
                                        width: auto;
                                        max-width: max-content;
                                        box-sizing: border-box;
                                    "
                                    onmouseover="this.style.background='<?php echo $style_btn_bg_hover; ?>';this.style.color='<?php echo $style_btn_text_hover; ?>';"
                                    onmouseout="this.style.background='<?php echo $style_btn_bg; ?>';this.style.color='<?php echo $style_btn_text; ?>';"
                                >
                                    <?php echo esc_html($btn_txt ?: 'Learn More'); ?>
                                </a>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php if ($atts['pagination'] === 'true') : ?>
                <div class="swiper-pagination"></div>
            <?php endif; ?>
        </div>
    </div>
    <style>
        .acf-swiper {
            min-height: <?php echo $style_min_height; ?>px;
        }
        .acf-swiper .swiper {
            min-height: inherit;
            height: auto;
        }
        .acf-swiper .swiper-wrapper,
        .acf-swiper .swiper-slide,
        .acf-swiper .slide-inner {
            min-height: inherit;
        }
        .acf-swiper .slide-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }
        .acf-swiper .slide-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
        }
        .acf-swiper .slide-inner {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            gap: <?php echo $style_gap; ?>px;
            color: <?php echo $style_text; ?>;
        }
        .acf-swiper .swiper-pagination-bullet {
            background: <?php echo $dot_inactive; ?>;
            opacity: 1;
            transition: background 160ms ease, transform 160ms ease;
            width: <?php echo $style_dot_size; ?>px;
            height: <?php echo $style_dot_size; ?>px;
        }
        .acf-swiper .swiper-pagination-bullet-active {
            background: <?php echo $dot_active; ?>;
            transform: scale(1.1);
        }
        .acf-swiper .swiper-pagination {
            position: absolute;
            bottom: 16px;
            left: 0;
            right: 0;
            z-index: 3;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        <?php if (!empty($custom_css)) : echo $custom_css; endif; ?>
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('acf_swiper', __NAMESPACE__ . '\\shortcode');

/**
 * Settings registration.
 *
 * @return void
 */
function register_settings(): void
{
    register_setting('acfswiper_settings_group', 'acfswiper_settings', [
        'type' => 'array',
        'sanitize_callback' => __NAMESPACE__ . '\\sanitize_settings',
        'default' => defaults(),
    ]);
}
add_action('admin_init', __NAMESPACE__ . '\\register_settings');

/**
 * Sanitize settings.
 *
 * @param array $input
 * @return array
 */
function sanitize_settings(array $input): array
{
    $defaults = defaults();
    $clean = $defaults;

    $allowed_sources = ['acf', 'builtin'];
    $source = $input['data_source'] ?? $defaults['data_source'];
    $clean['data_source'] = in_array($source, $allowed_sources, true) ? $source : $defaults['data_source'];

    $clean['acf']['repeater'] = sanitize_key($input['acf']['repeater'] ?? $defaults['acf']['repeater']);
    $clean['acf']['heading'] = sanitize_key($input['acf']['heading'] ?? $defaults['acf']['heading']);
    $clean['acf']['text'] = sanitize_key($input['acf']['text'] ?? $defaults['acf']['text']);
    $clean['acf']['button_text'] = sanitize_key($input['acf']['button_text'] ?? $defaults['acf']['button_text']);
    $clean['acf']['button_link'] = sanitize_key($input['acf']['button_link'] ?? $defaults['acf']['button_link']);
    $clean['acf']['image'] = sanitize_key($input['acf']['image'] ?? $defaults['acf']['image']);

    $clean['builtin']['slides'] = [];
    if (!empty($input['builtin']['slides']) && is_array($input['builtin']['slides'])) {
        foreach (array_values($input['builtin']['slides']) as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $heading = sanitize_text_field($slide['heading'] ?? '');
            $text = wp_kses_post($slide['text'] ?? '');
            $btn_text = sanitize_text_field($slide['button_text'] ?? '');
            $btn_link = isset($slide['button_link']) ? esc_url_raw($slide['button_link']) : '';
            $image_val = $slide['image'] ?? '';
            $image = '';
            if (is_numeric($image_val)) {
                $image = (string) (int) $image_val;
            } elseif (is_string($image_val)) {
                $image = esc_url_raw($image_val);
            }

            if ($heading || $text || $btn_text || $btn_link || $image) {
                $clean['builtin']['slides'][] = [
                    'heading' => $heading,
                    'text' => $text,
                    'button_text' => $btn_text,
                    'button_link' => $btn_link,
                    'image' => $image,
                ];
            }
        }
    }

    $clean['slider']['min_height'] = (int) ($input['slider']['min_height'] ?? $defaults['slider']['min_height']);
    $clean['slider']['gap'] = (int) ($input['slider']['gap'] ?? $defaults['slider']['gap']);
    $clean['slider']['content_max_width'] = (int) ($input['slider']['content_max_width'] ?? $defaults['slider']['content_max_width']);
    $clean['slider']['content_padding'] = (int) ($input['slider']['content_padding'] ?? $defaults['slider']['content_padding']);
    $clean['slider']['slides_per_view'] = (int) ($input['slider']['slides_per_view'] ?? $defaults['slider']['slides_per_view']);
    $clean['slider']['space_between'] = (int) ($input['slider']['space_between'] ?? $defaults['slider']['space_between']);
    $clean['slider']['loop'] = !empty($input['slider']['loop']);
    $clean['slider']['speed'] = (int) ($input['slider']['speed'] ?? $defaults['slider']['speed']);
    $clean['slider']['autoplay'] = !empty($input['slider']['autoplay']);
    $clean['slider']['autoplay_delay'] = (int) ($input['slider']['autoplay_delay'] ?? $defaults['slider']['autoplay_delay']);
    $clean['slider']['pagination'] = !empty($input['slider']['pagination']);

    $colors = ['text_color', 'button_bg', 'button_text', 'button_bg_hover', 'button_text_hover', 'dot_active', 'dot_inactive', 'overlay'];
    foreach ($colors as $key) {
        $clean['styles'][$key] = sanitize_text_field($input['styles'][$key] ?? $defaults['styles'][$key]);
    }
    $clean['styles']['button_radius'] = (int) ($input['styles']['button_radius'] ?? $defaults['styles']['button_radius']);
    $clean['styles']['dot_size'] = (int) ($input['styles']['dot_size'] ?? $defaults['styles']['dot_size']);
    $clean['styles']['custom_css'] = wp_kses_post($input['styles']['custom_css'] ?? $defaults['styles']['custom_css']);

    return $clean;
}

/**
 * Settings page markup.
 *
 * @return void
 */
function render_settings_page(): void
{
    $settings = get_settings();
    if (function_exists('wp_enqueue_media')) {
        wp_enqueue_media();
    }
    ?>
    <div class="wrap">
        <h1>ACF Swiper Settings</h1>
        <p>Use the shortcode <code>[acf_swiper]</code> on any page/post. Optional overrides: <code>post_id</code>, <code>field</code>, <code>slides_per_view</code>, <code>space_between</code>, <code>loop</code>, <code>speed</code>, <code>autoplay</code>, <code>autoplay_delay</code>, <code>min_height</code>, <code>gap</code>, and the style keys below.</p>
        <p><button type="button" class="button" onclick="navigator.clipboard.writeText('[acf_swiper]'); alert('Shortcode copied');">Copy shortcode</button></p>
        <form method="post" action="options.php">
            <?php settings_fields('acfswiper_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr><th colspan="2"><h2>Data Source</h2></th></tr>
                <tr>
                    <th scope="row"><label for="data-source-acf">Source</label></th>
                    <td>
                        <label><input type="radio" name="acfswiper_settings[data_source]" id="data-source-acf" value="acf" <?php checked($settings['data_source'], 'acf'); ?>> ACF repeater (default)</label><br>
                        <label><input type="radio" name="acfswiper_settings[data_source]" id="data-source-builtin" value="builtin" <?php checked($settings['data_source'], 'builtin'); ?>> Built-in slides (no ACF required)</label>
                        <p class="description">Shortcode respects <code>source="acf"</code> or <code>source="builtin"</code> if you want to override per instance.</p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2>ACF Fields</h2></th></tr>
                <tr>
                    <th scope="row"><label for="acf-repeater">Repeater field</label></th>
                    <td><input name="acfswiper_settings[acf][repeater]" id="acf-repeater" type="text" value="<?php echo esc_attr($settings['acf']['repeater']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="acf-heading">Heading field</label></th>
                    <td><input name="acfswiper_settings[acf][heading]" id="acf-heading" type="text" value="<?php echo esc_attr($settings['acf']['heading']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="acf-text">Text field</label></th>
                    <td><input name="acfswiper_settings[acf][text]" id="acf-text" type="text" value="<?php echo esc_attr($settings['acf']['text']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="acf-button-text">Button text field</label></th>
                    <td><input name="acfswiper_settings[acf][button_text]" id="acf-button-text" type="text" value="<?php echo esc_attr($settings['acf']['button_text']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="acf-button-link">Button link field</label></th>
                    <td><input name="acfswiper_settings[acf][button_link]" id="acf-button-link" type="text" value="<?php echo esc_attr($settings['acf']['button_link']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="acf-image">Image field</label></th>
                    <td><input name="acfswiper_settings[acf][image]" id="acf-image" type="text" value="<?php echo esc_attr($settings['acf']['image']); ?>" class="regular-text"></td>
                </tr>

                <tr class="js-acfswiper-builtin"><th colspan="2"><h2>Built-in Slides</h2></th></tr>
                <tr class="js-acfswiper-builtin">
                    <th scope="row">Slides</th>
                    <td>
                        <p class="description">Use this if you prefer not to use ACF. Slides are stored in options.</p>
                        <div id="acfswiper-builtin-slides" data-next-index="<?php echo isset($settings['builtin']['slides']) ? count($settings['builtin']['slides']) : 0; ?>">
                            <?php
                            $slides = $settings['builtin']['slides'] ?? [];
                            if (!empty($slides)) :
                                foreach ($slides as $i => $slide) :
                                    $heading = $slide['heading'] ?? '';
                                    $text = $slide['text'] ?? '';
                                    $btn_text = $slide['button_text'] ?? '';
                                    $btn_link = $slide['button_link'] ?? '';
                                    $image = $slide['image'] ?? '';
                                    ?>
                                    <div class="acfswiper-slide-card" data-index="<?php echo (int) $i; ?>">
                                        <div class="acfswiper-slide-card__head">
                                            <strong>Slide #<?php echo (int) ($i + 1); ?></strong>
                                            <button type="button" class="button-link acfswiper-remove-slide">Remove</button>
                                        </div>
                                        <p><label>Heading<br><input type="text" class="regular-text" name="acfswiper_settings[builtin][slides][<?php echo (int) $i; ?>][heading]" value="<?php echo esc_attr($heading); ?>"></label></p>
                                        <p><label>Text<br><textarea name="acfswiper_settings[builtin][slides][<?php echo (int) $i; ?>][text]" rows="3" class="large-text"><?php echo esc_textarea($text); ?></textarea></label></p>
                                        <p><label>Button text<br><input type="text" class="regular-text" name="acfswiper_settings[builtin][slides][<?php echo (int) $i; ?>][button_text]" value="<?php echo esc_attr($btn_text); ?>"></label></p>
                                        <p><label>Button link (URL)<br><input type="url" class="regular-text" name="acfswiper_settings[builtin][slides][<?php echo (int) $i; ?>][button_link]" value="<?php echo esc_attr($btn_link); ?>"></label></p>
                                        <div class="acfswiper-image-field">
                                            <label>Image</label>
                                            <div class="acfswiper-image-row">
                                                <input type="text" class="regular-text acfswiper-image-input" name="acfswiper_settings[builtin][slides][<?php echo (int) $i; ?>][image]" value="<?php echo esc_attr($image); ?>" placeholder="ID or URL">
                                                <button type="button" class="button acfswiper-select-image">Select image</button>
                                                <button type="button" class="button-link acfswiper-clear-image">Clear</button>
                                            </div>
                                            <div class="acfswiper-image-preview">
                                                <?php if (!empty($image)) : ?>
                                                    <img src="<?php echo esc_url(is_numeric($image) ? wp_get_attachment_image_url((int) $image, 'thumbnail') : $image); ?>" alt="" />
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                        <p><button type="button" class="button" id="acfswiper-add-slide">Add slide</button></p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2>Slider Options</h2></th></tr>
                <tr>
                    <th scope="row"><label for="slider-min-height">Min height (px)</label></th>
                    <td><input name="acfswiper_settings[slider][min_height]" id="slider-min-height" type="number" value="<?php echo esc_attr($settings['slider']['min_height']); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="slider-gap">Gap (px)</label></th>
                    <td><input name="acfswiper_settings[slider][gap]" id="slider-gap" type="number" value="<?php echo esc_attr($settings['slider']['gap']); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="slider-content-max">Content max width (px)</label></th>
                    <td><input name="acfswiper_settings[slider][content_max_width]" id="slider-content-max" type="number" value="<?php echo esc_attr($settings['slider']['content_max_width']); ?>" class="small-text" min="0"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="slider-content-pad">Content padding (px)</label></th>
                    <td><input name="acfswiper_settings[slider][content_padding]" id="slider-content-pad" type="number" value="<?php echo esc_attr($settings['slider']['content_padding']); ?>" class="small-text" min="0"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="slider-spv">Slides per view</label></th>
                    <td><input name="acfswiper_settings[slider][slides_per_view]" id="slider-spv" type="number" value="<?php echo esc_attr($settings['slider']['slides_per_view']); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="slider-space">Space between (px)</label></th>
                    <td><input name="acfswiper_settings[slider][space_between]" id="slider-space" type="number" value="<?php echo esc_attr($settings['slider']['space_between']); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row">Loop</th>
                    <td><label><input type="checkbox" name="acfswiper_settings[slider][loop]" <?php checked($settings['slider']['loop']); ?>> Enable loop</label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="slider-speed">Speed (ms)</label></th>
                    <td><input name="acfswiper_settings[slider][speed]" id="slider-speed" type="number" value="<?php echo esc_attr($settings['slider']['speed']); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row">Autoplay</th>
                    <td>
                        <label><input type="checkbox" name="acfswiper_settings[slider][autoplay]" <?php checked($settings['slider']['autoplay']); ?>> Enable autoplay</label><br>
                        <label for="slider-autoplay-delay">Delay (ms)</label>
                        <input name="acfswiper_settings[slider][autoplay_delay]" id="slider-autoplay-delay" type="number" value="<?php echo esc_attr($settings['slider']['autoplay_delay']); ?>" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Pagination</th>
                    <td><label><input type="checkbox" name="acfswiper_settings[slider][pagination]" <?php checked($settings['slider']['pagination']); ?>> Show pagination bullets</label></td>
                </tr>

                <tr><th colspan="2"><h2>Styles</h2></th></tr>
                <?php
                $style_fields = [
                    'text_color' => 'Text color',
                    'button_bg' => 'Button background',
                    'button_text' => 'Button text',
                    'button_bg_hover' => 'Button background (hover)',
                    'button_text_hover' => 'Button text (hover)',
                    'button_radius' => 'Button radius (px)',
                    'dot_size' => 'Dot size (px)',
                    'dot_active' => 'Dot active',
                    'dot_inactive' => 'Dot inactive',
                    'overlay' => 'Overlay color',
                ];
                foreach ($style_fields as $key => $label) :
                    $val = $settings['styles'][$key];
                    $is_color = !in_array($key, ['button_radius', 'dot_size'], true);
                    $picker_val = ($is_color && is_string($val) && strpos($val, '#') === 0) ? $val : '#ffffff';
                    ?>
                    <tr>
                        <th scope="row"><label for="style-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                        <td>
                            <?php if ($is_color) : ?>
                                <input type="color" value="<?php echo esc_attr($picker_val); ?>" oninput="document.getElementById('style-text-<?php echo esc_attr($key); ?>').value=this.value;">
                                <input name="acfswiper_settings[styles][<?php echo esc_attr($key); ?>]" id="style-text-<?php echo esc_attr($key); ?>" type="text" value="<?php echo esc_attr($val); ?>" class="regular-text" placeholder="#hex or rgba()">
                            <?php else : ?>
                                <input name="acfswiper_settings[styles][<?php echo esc_attr($key); ?>]" id="style-text-<?php echo esc_attr($key); ?>" type="number" value="<?php echo esc_attr($val); ?>" class="small-text">
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th scope="row"><label for="style-custom-css">Custom CSS</label></th>
                    <td><textarea name="acfswiper_settings[styles][custom_css]" id="style-custom-css" rows="5" class="large-text code" placeholder=".acf-swiper h1 { font-size: 48px; }"><?php echo esc_textarea($settings['styles']['custom_css']); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <script type="text/template" id="acfswiper-slide-template">
        <div class="acfswiper-slide-card" data-index="__i__">
            <div class="acfswiper-slide-card__head">
                <strong>Slide #__n__</strong>
                <button type="button" class="button-link acfswiper-remove-slide">Remove</button>
            </div>
            <p><label>Heading<br><input type="text" class="regular-text" name="acfswiper_settings[builtin][slides][__i__][heading]" value=""></label></p>
            <p><label>Text<br><textarea name="acfswiper_settings[builtin][slides][__i__][text]" rows="3" class="large-text"></textarea></label></p>
            <p><label>Button text<br><input type="text" class="regular-text" name="acfswiper_settings[builtin][slides][__i__][button_text]" value=""></label></p>
            <p><label>Button link (URL)<br><input type="url" class="regular-text" name="acfswiper_settings[builtin][slides][__i__][button_link]" value=""></label></p>
            <div class="acfswiper-image-field">
                <label>Image</label>
                <div class="acfswiper-image-row">
                    <input type="text" class="regular-text acfswiper-image-input" name="acfswiper_settings[builtin][slides][__i__][image]" value="" placeholder="ID or URL">
                    <button type="button" class="button acfswiper-select-image">Select image</button>
                    <button type="button" class="button-link acfswiper-clear-image">Clear</button>
                </div>
                <div class="acfswiper-image-preview"></div>
            </div>
        </div>
    </script>
    <style>
        .acfswiper-slide-card {
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 12px 12px 4px;
            margin-bottom: 12px;
            background: #fff;
        }
        .acfswiper-slide-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .acfswiper-image-field {
            margin: 10px 0;
        }
        .acfswiper-image-row {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        .acfswiper-image-row input.regular-text {
            min-width: 260px;
        }
        .acfswiper-image-preview {
            margin-top: 6px;
        }
        .acfswiper-image-preview img {
            max-width: 160px;
            height: auto;
            border-radius: 6px;
            border: 1px solid #c3c4c7;
            display: block;
        }
    </style>
    <script>
        (function() {
            const toggleBuiltins = () => {
                const value = document.querySelector('input[name="acfswiper_settings[data_source]"]:checked')?.value || 'acf';
                document.querySelectorAll('.js-acfswiper-builtin').forEach(row => {
                    row.style.display = value === 'builtin' ? '' : 'none';
                });
            };

            const radios = document.querySelectorAll('input[name="acfswiper_settings[data_source]"]');
            radios.forEach(r => r.addEventListener('change', toggleBuiltins));
            toggleBuiltins();

            const slidesWrap = document.getElementById('acfswiper-builtin-slides');
            const template = document.getElementById('acfswiper-slide-template');
            const addBtn = document.getElementById('acfswiper-add-slide');

            if (addBtn && slidesWrap && template) {
                addBtn.addEventListener('click', () => {
                    const next = parseInt(slidesWrap.dataset.nextIndex || '0', 10);
                    const html = template.innerHTML.replace(/__i__/g, next).replace(/__n__/g, next + 1);
                    const holder = document.createElement('div');
                    holder.innerHTML = html.trim();
                    const node = holder.firstElementChild;
                    slidesWrap.appendChild(node);
                    slidesWrap.dataset.nextIndex = next + 1;
                });

                slidesWrap.addEventListener('click', (e) => {
                    const target = e.target;
                    if (target && target.classList.contains('acfswiper-remove-slide')) {
                        const card = target.closest('.acfswiper-slide-card');
                        if (card) {
                            card.remove();
                        }
                        return;
                    }
                    if (target && target.classList.contains('acfswiper-select-image')) {
                        if (!window.wp || !wp.media) {
                            alert('Media library not available.');
                            return;
                        }
                        const card = target.closest('.acfswiper-slide-card');
                        const input = card?.querySelector('.acfswiper-image-input');
                        const preview = card?.querySelector('.acfswiper-image-preview');
                        const frame = wp.media({
                            title: 'Select slide image',
                            multiple: false,
                            library: { type: 'image' },
                        });
                        frame.on('select', () => {
                            const attachment = frame.state().get('selection').first().toJSON();
                            if (input) {
                                input.value = attachment.id || attachment.url || '';
                            }
                            if (preview) {
                                preview.innerHTML = attachment.url ? '<img src="' + attachment.url + '" alt="">' : '';
                            }
                        });
                        frame.open();
                        return;
                    }
                    if (target && target.classList.contains('acfswiper-clear-image')) {
                        const card = target.closest('.acfswiper-slide-card');
                        const input = card?.querySelector('.acfswiper-image-input');
                        const preview = card?.querySelector('.acfswiper-image-preview');
                        if (input) input.value = '';
                        if (preview) preview.innerHTML = '';
                    }
                });
            }
        }());
    </script>
    <?php
}

/**
 * Register settings page.
 *
 * @return void
 */
function add_settings_page(): void
{
    add_options_page(
        'ACF Swiper',
        'ACF Swiper',
        'manage_options',
        'acfswiper',
        __NAMESPACE__ . '\\render_settings_page'
    );
}
add_action('admin_menu', __NAMESPACE__ . '\\add_settings_page');
