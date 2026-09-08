import Alpine from 'alpinejs';
import gsap from 'gsap';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const banner = document.querySelector('[data-guest-banner]');

    if (banner) {
        gsap.from(banner, { y: -16, opacity: 0, duration: 0.6, ease: 'power2.out' });
    }
});
