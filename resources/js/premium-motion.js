import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

export function initPremiumMotion() {
    if (!document.querySelector('.site-header')) return;
    gsap.registerPlugin(ScrollTrigger);
    const media = gsap.matchMedia();

    media.add('(prefers-reduced-motion: no-preference)', () => {
        // Animate the natural heading, preserving wrapping, accents and accessible text.
        document.querySelectorAll('[data-word-reveal]').forEach(heading => {
            gsap.from(heading, {
                y: 12, opacity: .7, duration: .7, ease: 'power2.out', clearProps: 'transform,opacity',
                scrollTrigger: { trigger: heading, start: 'top 95%', once: true },
            });
        });
        gsap.from('.hero-kicker, .hero-bottom-copy, .hero-editorial', { y: 12, duration: .7, stagger: .08, ease: 'power2.out', clearProps: 'transform' });
        gsap.from('.hero-image', { scale: 1.065, duration: 2, ease: 'power2.out' });
        gsap.from('.brand-logo', { y: -5, duration: .6, clearProps: 'transform' });
        document.querySelectorAll('.reveal').forEach(element => gsap.from(element, {
            scrollTrigger: { trigger: element, start: 'top 94%', once: true }, y: 16, opacity: .7, duration: .7, ease: 'power2.out', clearProps: 'transform,opacity',
        }));
        const frames = document.querySelectorAll('[data-mask]');
        frames.forEach(frame => {
            const image = frame.matches('img') ? frame : frame.querySelector('img');
            if (!image) return;
            // Mask only photography: overlay captions remain visible throughout.
            gsap.fromTo(image, { clipPath: 'inset(0 0 5% 0)' }, {
                clipPath: 'inset(0% 0 0% 0)', duration: 1.25, ease: 'power3.out',
                scrollTrigger: { trigger: frame, start: 'top 92%', once: true },
                clearProps: 'clipPath',
            });
            // Card images keep their existing CSS hover zoom.
            if (image && !frame.closest('.service-feature, .project-card, .inspiration-rail')) {
                gsap.from(image, {
                    scale: 1.07, duration: 1.5, ease: 'power2.out', clearProps: 'transform',
                    scrollTrigger: { trigger: frame, start: 'top 92%', once: true },
                });
            }
        });
        document.querySelectorAll('.journey-stages').forEach(stages => gsap.fromTo(stages.querySelector('.journey-progress'), { scaleY: 0 }, {
            scaleY: 1, ease: 'none', scrollTrigger: { trigger: stages, start: 'top 65%', end: 'bottom 65%', scrub: .5 },
        }));
    });

    media.add('(min-width: 1024px) and (prefers-reduced-motion: no-preference)', () => {
        document.querySelectorAll('[data-parallax]').forEach(element => gsap.fromTo(element, { yPercent: -3 }, {
            yPercent: 3, ease: 'none', scrollTrigger: { trigger: element.parentElement, start: 'top bottom', end: 'bottom top', scrub: .8 },
        }));
    });

    document.querySelectorAll('.editorial-accordion').forEach(details => {
        const summary = details.querySelector('summary');
        const content = details.querySelector('.accordion-body');
        let closing = false;
        summary.addEventListener('click', event => {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            event.preventDefault();
            gsap.killTweensOf(content);
            if (details.open && !closing) {
                closing = true;
                gsap.to(content, { height: 0, opacity: 0, duration: .32, ease: 'power2.inOut', onComplete: () => {
                    details.open = false; closing = false;
                    gsap.set(content, { clearProps: 'height,opacity' });
                    ScrollTrigger.refresh();
                } });
            } else {
                closing = false;
                details.open = true;
                gsap.fromTo(content, { height: 0, opacity: 0 }, { height: 'auto', opacity: 1, duration: .4, ease: 'power2.out', onComplete: () => {
                    gsap.set(content, { clearProps: 'height,opacity' });
                    ScrollTrigger.refresh();
                } });
            }
        });
    });
    let refreshFrame;
    const refreshLayout = () => {
        cancelAnimationFrame(refreshFrame);
        refreshFrame = requestAnimationFrame(() => ScrollTrigger.refresh());
    };
    window.addEventListener('portfolio:filter', refreshLayout);
    window.addEventListener('studio:layout', refreshLayout);
    window.addEventListener('pageshow', refreshLayout);
    window.addEventListener('load', refreshLayout, { once: true });
    if (document.fonts) document.fonts.ready.then(refreshLayout);
    document.querySelectorAll('img').forEach(image => {
        if (!image.complete) {
            image.addEventListener('load', refreshLayout, { once: true });
            image.addEventListener('error', refreshLayout, { once: true });
        }
    });
    document.querySelectorAll('[data-back-to-top]').forEach(link => link.addEventListener('click', event => {
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
    }));

    media.add('(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference) and (forced-colors: none)', () => {
        const ring = document.createElement('div');
        ring.className = 'premium-cursor';
        ring.setAttribute('aria-hidden', 'true');
        document.body.append(ring);
        const moveX = gsap.quickTo(ring, 'x', { duration: .22, ease: 'power3.out' });
        const moveY = gsap.quickTo(ring, 'y', { duration: .22, ease: 'power3.out' });
        let hasPosition = false;
        const onPointerMove = event => {
            if (event.pointerType !== 'mouse') { ring.classList.remove('visible'); return; }
            if (!hasPosition) {
                gsap.set(ring, { x: event.clientX, y: event.clientY });
                hasPosition = true;
            }
            moveX(event.clientX);
            moveY(event.clientY);
            ring.classList.add('visible');
        };
        const hideRing = () => ring.classList.remove('visible');
        const onPointerOut = event => { if (!event.relatedTarget) hideRing(); };
        window.addEventListener('pointermove', onPointerMove, { passive: true });
        window.addEventListener('blur', hideRing);
        document.addEventListener('pointerout', onPointerOut);

        const cleanups = [];
        document.querySelectorAll('.button, .header-cta, .project-card > a').forEach(target => {
            const isCard = target.matches('.project-card > a');
            const moving = isCard ? target.querySelector('.image-arrow') : target;
            if (!moving) return;
            const x = gsap.quickTo(moving, 'x', { duration: .35, ease: 'power3.out' });
            const y = gsap.quickTo(moving, 'y', { duration: .35, ease: 'power3.out' });
            let bounds;
            const enter = event => {
                if (event.pointerType !== 'mouse') return;
                bounds = target.getBoundingClientRect();
                ring.classList.add('engaged');
                if (isCard) ring.classList.add('view-project');
            };
            const move = event => {
                if (event.pointerType !== 'mouse' || !bounds || target.matches(':disabled')) return;
                const strength = isCard ? .035 : .14;
                const limit = isCard ? 10 : 7;
                x(gsap.utils.clamp(-limit, limit, (event.clientX - bounds.left - bounds.width / 2) * strength));
                y(gsap.utils.clamp(-limit, limit, (event.clientY - bounds.top - bounds.height / 2) * strength));
            };
            const leave = () => {
                bounds = null;
                x(0); y(0);
                ring.classList.remove('engaged', 'view-project');
            };
            target.addEventListener('pointerenter', enter);
            target.addEventListener('pointermove', move, { passive: true });
            target.addEventListener('pointerleave', leave);
            target.addEventListener('focus', leave);
            window.addEventListener('scroll', leave, { passive: true });
            cleanups.push(() => {
                target.removeEventListener('pointerenter', enter);
                target.removeEventListener('pointermove', move);
                target.removeEventListener('pointerleave', leave);
                target.removeEventListener('focus', leave);
                window.removeEventListener('scroll', leave);
                gsap.set(moving, { clearProps: 'transform' });
            });
        });
        return () => {
            cleanups.forEach(cleanup => cleanup());
            window.removeEventListener('pointermove', onPointerMove);
            window.removeEventListener('blur', hideRing);
            document.removeEventListener('pointerout', onPointerOut);
            ring.remove();
        };
    });
}
