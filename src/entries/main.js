// import '@/styles/main.scss'; // SASS causes build issues in this env, use CSS
import '@/styles/main.css';
// import Swiper from 'swiper/bundle';
// import 'swiper/css/bundle'; // Use CDN instead to avoid build issues

// Expose Swiper to global scope for legacy/inline scripts
// window.Swiper = Swiper;

console.log('Modern Frontend Loaded! V7 - CSS Only Build');

document.addEventListener('DOMContentLoaded', () => {
  // Helper function to safely initialize Swiper with error handling
  const initSwiper = (selector, config, name) => {
    try {
      const el = document.querySelector(selector);
      if (!el) {
        // console.warn(`[Swiper] Target element '${selector}' not found. Skipping ${name}.`);
        return;
      }
      
      // Safety check: Swiper needs a wrapper
      if (!el.querySelector('.swiper-wrapper')) {
         console.error(`[Swiper] Target '${selector}' exists but missing '.swiper-wrapper'. Structure is invalid for ${name}.`);
         return;
      }

      new Swiper(el, config);
      
    } catch (err) {
      console.error(`[Swiper] CRITICAL ERROR initializing ${name}:`, err);
    }
  };

  // 1. Product Swiper
  initSwiper(".product", {
      slidesPerView: 1,
      slidesPerGroup: 1,
      spaceBetween: 10,
      speed: 1000, // 1 second transition
      loop: true,
      autoplay: {
        delay: 5000, // 5 seconds delay
        disableOnInteraction: false,
      },
      pagination: {
        el: ".swiper-pagination",
        clickable: true,
      },
      navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      },
  }, "Product Swiper");

  // 2. Banner Swiper
  initSwiper(".banner_sect", {
      slidesPerView: 1,
      slidesPerGroup: 1,
      spaceBetween: 10,
      speed: 1000,
      loop: true,
      autoplay: {
          delay: 5000,
          disableOnInteraction: false,
      },
      touchRatio: 0,
      pagination: {
        el: ".swiper-banner-pagination",
        clickable: true,
      },
  }, "Banner Swiper");

  // 3. Mobile Banner Swiper
  initSwiper(".banner_mo_sect", {
      slidesPerView: 1,
      slidesPerGroup: 1,
      spaceBetween: 10,
      speed: 1000,
      loop: true,
      autoplay: {
          delay: 5000,
          disableOnInteraction: false,
      },
      touchRatio: 0,
      pagination: {
        el: ".swiper-mobanner-pagination",
        clickable: true,
      },
  }, "Mobile Banner Swiper");
});
