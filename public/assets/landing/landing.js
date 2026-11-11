(() => {
  'use strict';

  // The template showcase scrolls sideways by touch, trackpad, or keyboard;
  // the arrow buttons move it about one screen at a time for mouse users.
  const showcase = document.getElementById('templateShowcase');
  if (!showcase) return;

  const arrows = document.querySelectorAll('[data-showcase-step]');

  function updateArrows() {
    const max = showcase.scrollWidth - showcase.clientWidth - 2;
    arrows.forEach((button) => {
      const step = Number(button.dataset.showcaseStep);
      button.disabled = step < 0 ? showcase.scrollLeft <= 2 : showcase.scrollLeft >= max;
    });
  }

  arrows.forEach((button) => {
    button.addEventListener('click', () => {
      const distance = Math.max(240, showcase.clientWidth * 0.8);
      showcase.scrollBy({ left: Number(button.dataset.showcaseStep) * distance, behavior: 'smooth' });
    });
  });

  showcase.addEventListener('scroll', updateArrows, { passive: true });
  window.addEventListener('resize', updateArrows);
  updateArrows();
})();
