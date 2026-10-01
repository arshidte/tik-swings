// Motion system (§14 / §47 / §48). All effects respect reduced-motion.
import { $, $$ } from './util.js';

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// --- Scroll reveal ---------------------------------------------------------
export function initReveal() {
  const els = $$('.reveal');
  if (!els.length) return;
  if (reduced || !('IntersectionObserver' in window)) {
    els.forEach((el) => el.classList.add('is-visible'));
    return;
  }
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const delay = el.dataset.revealDelay || 0;
        el.style.transitionDelay = `${delay}ms`;
        el.classList.add('is-visible');
        io.unobserve(el);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
  els.forEach((el) => io.observe(el));
}

// --- Subtle hero parallax --------------------------------------------------
export function initParallax() {
  if (reduced) return;
  const els = $$('[data-parallax]');
  if (!els.length) return;
  let ticking = false;
  const update = () => {
    const vh = window.innerHeight;
    els.forEach((el) => {
      const speed = parseFloat(el.dataset.parallax) || 0.15;
      const rect = el.getBoundingClientRect();
      const offset = (rect.top + rect.height / 2 - vh / 2) * -speed;
      el.style.transform = `translate3d(0, ${offset.toFixed(1)}px, 0)`;
    });
    ticking = false;
  };
  window.addEventListener('scroll', () => {
    if (!ticking) {
      requestAnimationFrame(update);
      ticking = true;
    }
  }, { passive: true });
  update();
}

export function initAnimations() {
  initReveal();
  initParallax();
}
