<?php
/**
 * Plugin Name: ACF Swiper Shortcode
 * Description: Simple Swiper slider powered by ACF repeater fields. No builder dependency.
 * Version: 0.0.4
 * Author: Sinawi Web Design
 */

namespace ACFSwiperShortcode;

if (!defined('ABSPATH')) {
    exit;
}

const VERSION = '0.0.4';

/**
 * Default settings.
 *
 * @return array
 */
function defaults(): array
{
    return [
        'acf' => [
            'repeater' => 'slide',
            'heading' => 'slide_heading',
            'text' => 'slide_text',
            'button_text' => 'button_text',
            'button_link' => 'button_link',
            'image' => 'slide_background_image',
        ],
        'slider' => [
            'min_height' => 500,
            'gap' => 14,
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
 * Shortcode handler.
 *
 * @param array $atts
 * @return string
 */
function shortcode(array $atts = []): string
{
    if (!function_exists('have_rows')) {
        return '<!-- ACF not available -->';
    }

    $settings = get_settings();

    $atts = shortcode_atts(
        [
            'post_id' => get_the_ID(),
            'field' => $settings['acf']['repeater'],
            'slides_per_view' => $settings['slider']['slides_per_view'],
            'space_between' => $settings['slider']['space_between'],
            'loop' => $settings['slider']['loop'] ? 'true' : 'false',
            'speed' => $settings['slider']['speed'],
            'autoplay' => $settings['slider']['autoplay'] ? 'true' : 'false',
            'autoplay_delay' => $settings['slider']['autoplay_delay'],
            'min_height' => $settings['slider']['min_height'],
            'gap' => $settings['slider']['gap'],
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

    if (!have_rows($field, $post_id)) {
        return '<!-- No slides -->';
    }

    // Enqueue assets.
    wp_enqueue_style('acfswiper-swiper');
    wp_enqueue_style('acfswiper');
    wp_enqueue_script('acfswiper-swiper');
    wp_enqueue_script('acfswiper-init');

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
        <div class="swiper" style="min-height: <?php echo $style_min_height; ?>px;">
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

    $clean['acf']['repeater'] = sanitize_key($input['acf']['repeater'] ?? $defaults['acf']['repeater']);
    $clean['acf']['heading'] = sanitize_key($input['acf']['heading'] ?? $defaults['acf']['heading']);
    $clean['acf']['text'] = sanitize_key($input['acf']['text'] ?? $defaults['acf']['text']);
    $clean['acf']['button_text'] = sanitize_key($input['acf']['button_text'] ?? $defaults['acf']['button_text']);
    $clean['acf']['button_link'] = sanitize_key($input['acf']['button_link'] ?? $defaults['acf']['button_link']);
    $clean['acf']['image'] = sanitize_key($input['acf']['image'] ?? $defaults['acf']['image']);

    $clean['slider']['min_height'] = (int) ($input['slider']['min_height'] ?? $defaults['slider']['min_height']);
    $clean['slider']['gap'] = (int) ($input['slider']['gap'] ?? $defaults['slider']['gap']);
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
    ?>
    <div class="wrap">
        <h1>ACF Swiper Settings</h1>
        <p>Use the shortcode <code>[acf_swiper]</code> on any page/post. Optional overrides: <code>post_id</code>, <code>field</code>, <code>slides_per_view</code>, <code>space_between</code>, <code>loop</code>, <code>speed</code>, <code>autoplay</code>, <code>autoplay_delay</code>, <code>min_height</code>, <code>gap</code>, and the style keys below.</p>
        <p><button type="button" class="button" onclick="navigator.clipboard.writeText('[acf_swiper]'); alert('Shortcode copied');">Copy shortcode</button></p>
        <form method="post" action="options.php">
            <?php settings_fields('acfswiper_settings_group'); ?>
            <table class="form-table" role="presentation">
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
