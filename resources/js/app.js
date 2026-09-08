import gsap from 'gsap';

document.addEventListener('DOMContentLoaded', () => {
    const heroLines = document.querySelectorAll('[data-hero-line]');
    const heroCard = document.querySelector('[data-hero-card]');

    if (heroLines.length === 0 && !heroCard) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const timeline = gsap.timeline({ defaults: { ease: 'power3.out' } });

    if (heroLines.length > 0) {
        timeline.from(heroLines, { y: 24, opacity: 0, stagger: 0.08, duration: 0.6 });
    }

    if (heroCard) {
        timeline.from(heroCard, { y: 30, opacity: 0, duration: 0.8 }, heroLines.length > 0 ? '-=0.4' : 0);
    }
});
