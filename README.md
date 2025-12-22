# ACF Swiper Shortcode

Simple Swiper slider with two data sources: ACF repeater (ACF Pro) or a built-in slide editor (no ACF required). No builder dependency.

## Usage
- Activate the plugin.
- Configure defaults under **Settings → ACF Swiper** (data source, ACF field keys, built-in slides, slider options, styles).
- Add the shortcode where you want the slider:
  ```
  [acf_swiper]
  ```
  Optional overrides:
  - Data: `source` (`acf` or `builtin`), `post_id`, `field` (ACF repeater name when using ACF)
  - Layout: `slides_per_view`, `space_between`, `loop`, `speed`, `autoplay`, `autoplay_delay`, `min_height`, `gap`, `content_max_width`, `content_padding`
  - Styles: `text_color`, `button_bg`, `button_text`, `button_bg_hover`, `button_text_hover`, `dot_active`, `dot_inactive`, `overlay`, `custom_css`

## Data sources
- **ACF (default):** Uses your repeater/fields. Works when ACF Pro is active.
- **Built-in:** Manage slides in **Settings → ACF Swiper → Built-in Slides** (media picker included). Set the data source to “Built-in” or pass `source="builtin"` on the shortcode.

## ACF fields (defaults)
- Repeater: `slide`
- Subfields: `slide_heading`, `slide_text`, `button_text`, `button_link`, `slide_background_image`

## Assets
- Swiper CSS/JS from CDN.
- Plugin assets: `assets/css/swiper.css`, `assets/js/swiper-init.js` (includes structural styles if CDN is blocked).
