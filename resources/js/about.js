import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

export function initAboutPage() {
    const page = document.querySelector('.about-luxury');
    if (!page) return;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const rail = page.querySelector('.about-material-rail');
    const previous = page.querySelector('[data-material-prev]');
    const next = page.querySelector('[data-material-next]');
    const moveGallery = direction => {
        const step = rail.querySelector('figure').getBoundingClientRect().width + 24;
        rail.scrollBy({ left: direction * step, behavior: reduced.matches ? 'instant' : 'smooth' });
    };
    const updateControls = () => {
        previous.disabled = rail.scrollLeft <= 2;
        next.disabled = rail.scrollLeft >= rail.scrollWidth - rail.clientWidth - 2;
    };
    previous.addEventListener('click', () => moveGallery(-1));
    next.addEventListener('click', () => moveGallery(1));
    rail.addEventListener('keydown', event => {
        if (event.target !== rail || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        if (event.key === 'Home' || event.key === 'End') {
            rail.scrollTo({ left: event.key === 'Home' ? 0 : rail.scrollWidth, behavior: reduced.matches ? 'instant' : 'smooth' });
        } else moveGallery(event.key === 'ArrowRight' ? 1 : -1);
    });
    rail.addEventListener('scroll', updateControls, { passive: true });
    new ResizeObserver(updateControls).observe(rail);
    updateControls();

    gsap.registerPlugin(ScrollTrigger);
    const media = gsap.matchMedia();
    media.add('(prefers-reduced-motion: no-preference)', () => {
        gsap.from('.about-hero-image', { scale: 1.055, duration: 1.8, ease: 'power2.out', clearProps: 'transform' });
        gsap.from('.about-hero-copy > .eyebrow, .about-hero-copy > p, .about-hero-copy > a', { y: 16, opacity: .6, duration: .8, stagger: .12, clearProps: 'transform,opacity' });
        gsap.from('.about-principles article', { borderTopColor: 'transparent', duration: 1.1, stagger: .12, scrollTrigger: { trigger: '.about-principles', start: 'top 90%', once: true } });
    });
    media.add('(min-width: 768px) and (prefers-reduced-motion: no-preference)', () => {
        gsap.fromTo('.about-timeline-line > span', { scaleX: 0 }, { scaleX: 1, ease: 'none', scrollTrigger: { trigger: '.about-timeline', start: 'top 80%', end: 'bottom 45%', scrub: .5 } });
    });
    media.add('(max-width: 767px) and (prefers-reduced-motion: no-preference)', () => {
        gsap.fromTo('.about-timeline-line > span', { scaleY: 0 }, { scaleY: 1, ease: 'none', scrollTrigger: { trigger: '.about-timeline', start: 'top 70%', end: 'bottom 70%', scrub: .5 } });
    });
}
