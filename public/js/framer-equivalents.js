/**
 * framer-equivalents.js
 * Reusable GSAP animation helpers mirroring framer-motion's common patterns.
 * Works as a plain ES module (<script type="module">) without a build step.
 *
 * Feature: ui-ux-overhaul
 */

// 6.1 — Resolve GSAP from global scope (supports both module and non-module contexts)
const _gsap = (typeof gsap !== 'undefined') ? gsap : window.gsap;

// Guard: if GSAP is unavailable, warn and export no-ops
if (!_gsap) {
  console.warn('[framer-equivalents] GSAP not found. All animation helpers will be no-ops.');
}

/** Shared defaults for all animation helpers */
const defaults = {
  duration: 0.8,
  delay: 0,
  ease: 'power3.out',
};

/**
 * 6.2 — fadeIn
 * Animates target opacity from 0 → 1.
 *
 * @param {string|Element|NodeList} target
 * @param {Object} [options]
 * @param {number} [options.duration=0.8]
 * @param {number} [options.delay=0]
 * @param {string} [options.ease='power3.out']
 */
export function fadeIn(target, options = {}) {
  if (!_gsap) return;
  const opts = { ...defaults, ...options };
  _gsap.fromTo(
    target,
    { opacity: 0 },
    { opacity: 1, duration: opts.duration, delay: opts.delay, ease: opts.ease }
  );
}

/**
 * 6.3 — slideUp
 * Animates target from y: 40 → 0 combined with opacity 0 → 1.
 *
 * @param {string|Element|NodeList} target
 * @param {Object} [options]
 * @param {number} [options.duration=0.8]
 * @param {number} [options.delay=0]
 * @param {string} [options.ease='power3.out']
 */
export function slideUp(target, options = {}) {
  if (!_gsap) return;
  const opts = { ...defaults, ...options };
  _gsap.fromTo(
    target,
    { y: 40, opacity: 0 },
    { y: 0, opacity: 1, duration: opts.duration, delay: opts.delay, ease: opts.ease }
  );
}

/**
 * 6.4 — scaleIn
 * Animates target from scale: 0.92 → 1 combined with opacity 0 → 1.
 *
 * @param {string|Element|NodeList} target
 * @param {Object} [options]
 * @param {number} [options.duration=0.8]
 * @param {number} [options.delay=0]
 * @param {string} [options.ease='power3.out']
 */
export function scaleIn(target, options = {}) {
  if (!_gsap) return;
  const opts = { ...defaults, ...options };
  _gsap.fromTo(
    target,
    { scale: 0.92, opacity: 0 },
    { scale: 1, opacity: 1, duration: opts.duration, delay: opts.delay, ease: opts.ease }
  );
}

/**
 * 6.5 — staggerChildren
 * Applies slideUp to each child matching childSelector inside parent,
 * with a configurable stagger delay between each child.
 *
 * @param {string|Element} parent
 * @param {string} childSelector
 * @param {Object} [options]
 * @param {number} [options.duration=0.8]
 * @param {number} [options.delay=0]
 * @param {string} [options.ease='power3.out']
 * @param {number} [options.stagger=0.1]
 */
export function staggerChildren(parent, childSelector, options = {}) {
  if (!_gsap) return;
  const staggerDefaults = { ...defaults, stagger: 0.1 };
  const opts = { ...staggerDefaults, ...options };

  const parentEl = typeof parent === 'string' ? document.querySelector(parent) : parent;
  if (!parentEl) return;

  const children = parentEl.querySelectorAll(childSelector);
  if (!children.length) return;

  _gsap.fromTo(
    children,
    { y: 40, opacity: 0 },
    {
      y: 0,
      opacity: 1,
      duration: opts.duration,
      delay: opts.delay,
      ease: opts.ease,
      stagger: opts.stagger,
    }
  );
}
