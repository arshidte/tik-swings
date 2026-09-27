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

// --- Signature interaction: "Take a Moment" draggable swing (§48) ----------
export function initSwingInteraction() {
  const stage = $('[data-swing]');
  if (!stage) return;
  const swing = $('[data-swing-pivot]', stage);
  if (!swing) return;

  if (reduced) {
    // Gentle, non-interactive idle sway only.
    swing.style.animation = 'none';
    return;
  }

  let dragging = false;
  let angle = 0;          // current angle (deg)
  let velocity = 0;       // angular velocity
  let startX = 0;
  let startAngle = 0;
  let raf = null;
  const MAX = 22;

  const apply = () => { swing.style.transform = `rotate(${angle.toFixed(2)}deg)`; };

  // Spring-back physics when released (§47 spring-physics).
  const tick = () => {
    if (!dragging) {
      const stiffness = 0.012;
      const damping = 0.92;
      velocity += -angle * stiffness;
      velocity *= damping;
      angle += velocity;
      apply();
      if (Math.abs(angle) < 0.05 && Math.abs(velocity) < 0.05) {
        angle = 0; velocity = 0; apply();
        raf = null;
        return;
      }
    }
    raf = requestAnimationFrame(tick);
  };

  const pointerX = (e) => (e.touches ? e.touches[0].clientX : e.clientX);

  const onDown = (e) => {
    dragging = true;
    startX = pointerX(e);
    startAngle = angle;
    stage.classList.add('cursor-grabbing');
    if (raf) cancelAnimationFrame(raf);
    raf = requestAnimationFrame(tick);
  };
  const onMove = (e) => {
    if (!dragging) return;
    const dx = pointerX(e) - startX;
    const next = startAngle + dx * 0.08;
    velocity = next - angle;
    angle = Math.max(-MAX, Math.min(MAX, next));
    apply();
    if (e.cancelable && e.touches) e.preventDefault();
  };
  const onUp = () => {
    if (!dragging) return;
    dragging = false;
    stage.classList.remove('cursor-grabbing');
    if (!raf) raf = requestAnimationFrame(tick);
  };

  stage.addEventListener('mousedown', onDown);
  window.addEventListener('mousemove', onMove);
  window.addEventListener('mouseup', onUp);
  stage.addEventListener('touchstart', onDown, { passive: true });
  window.addEventListener('touchmove', onMove, { passive: false });
  window.addEventListener('touchend', onUp);

  // Keyboard nudge for accessibility.
  stage.setAttribute('tabindex', '0');
  stage.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') { angle = Math.max(-MAX, angle - 6); velocity = -2; apply(); if (!raf) raf = requestAnimationFrame(tick); }
    if (e.key === 'ArrowRight') { angle = Math.min(MAX, angle + 6); velocity = 2; apply(); if (!raf) raf = requestAnimationFrame(tick); }
  });

  // A small initial nudge to invite interaction.
  angle = 8; velocity = 0; apply();
  raf = requestAnimationFrame(tick);
}

export function initAnimations() {
  initReveal();
  initParallax();
  initSwingInteraction();
}
