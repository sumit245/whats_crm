/* custom.js — Scroll reveals + nav pill enhancement */
(function () {
  'use strict';

  /* ── Scroll reveal via IntersectionObserver ──────────── */
  const revealEls = document.querySelectorAll('[data-reveal]');

  if (revealEls.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          const delay = parseInt(entry.target.dataset.revealDelay || '0', 10);
          setTimeout(function () {
            entry.target.classList.add('revealed');
          }, delay);
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.08, rootMargin: '0px 0px -48px 0px' }
    );

    revealEls.forEach(function (el) { observer.observe(el); });
  } else {
    /* Fallback: reveal everything immediately for old browsers */
    revealEls.forEach(function (el) { el.classList.add('revealed'); });
  }

  /* ── Hero elements reveal on load ───────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    var heroReveal = document.querySelectorAll('[data-reveal-hero]');
    heroReveal.forEach(function (el, i) {
      setTimeout(function () {
        el.classList.add('revealed');
      }, 80 + i * 90);
    });
  });

  /* ── Stagger child items on bento grid entry ─────────── */
  var bentoShells = document.querySelectorAll('.bento-grid .db-shell[data-reveal]');
  bentoShells.forEach(function (shell, i) {
    shell.dataset.revealDelay = String(i * 70);
  });

})();
