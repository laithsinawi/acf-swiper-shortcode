(() => {
  const ready = (fn) => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  };

  const parseBool = (val, fallback = false) => {
    if (val === undefined || val === null) return fallback;
    if (typeof val === 'boolean') return val;
    return val === 'true' || val === '1' || val === 1;
  };

  ready(() => {
    if (typeof Swiper === 'undefined') return;

    document.querySelectorAll('[data-acf-swiper] .swiper').forEach((container) => {
      if (container.dataset.swiperInitialized === 'true') return;

      const parent = container.closest('[data-acf-swiper]');
      const parsedNumber = (val, fallback) => {
        if (val === undefined || val === null || val === '') return fallback;
        const n = Number(val);
        return Number.isNaN(n) ? fallback : n;
      };
      const opts = {
        slidesPerView: parsedNumber(parent.dataset.slidesPerView, 1),
        spaceBetween: parsedNumber(parent.dataset.spaceBetween, 24),
        speed: parsedNumber(parent.dataset.speed, 600),
        loop: parseBool(parent.dataset.loop, true),
      };

      const autoplay = parseBool(parent.dataset.autoplay, true);
      const autoplayDelay = parsedNumber(parent.dataset.autoplayDelay, NaN);
      if (autoplay) {
        opts.autoplay = { delay: Number.isNaN(autoplayDelay) ? 4000 : autoplayDelay };
      }

      const pagination = container.querySelector('.swiper-pagination');
      if (pagination) {
        opts.pagination = { el: pagination, clickable: true };
      }

      /* eslint-disable no-new */
      new Swiper(container, opts);
      /* eslint-enable no-new */

      container.dataset.swiperInitialized = 'true';
    });
  });
})();
