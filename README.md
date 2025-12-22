# ACF Swiper Shortcode

Simple Swiper slider powered by ACF repeater fields. No builder dependency.

## Usage
- Activate the plugin.
- Set defaults under **Settings → ACF Swiper** (ACF field keys, styles, slider options).
- Add the shortcode where you want the slider:
  ```
  [acf_swiper]
  ```
  Optional overrides:
  - `post_id` (defaults to current post)
  - `field` (ACF repeater name, defaults to settings)
  - `slides_per_view`, `space_between`, `loop`, `speed`, `autoplay`, `autoplay_delay`, `min_height`, `gap`
  - `text_color`, `button_bg`, `button_text`, `button_bg_hover`, `button_text_hover`, `dot_active`, `dot_inactive`, `overlay`

## ACF fields (defaults)
- Repeater: `slide`
- Subfields: `slide_heading`, `slide_text`, `button_text`, `button_link`, `slide_background_image`

## Assets
- Swiper CSS/JS from CDN.
- Plugin assets: `assets/css/swiper.css`, `assets/js/swiper-init.js`.
