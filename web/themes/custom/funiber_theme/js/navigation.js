/**
 * @file
 * Navigation behaviors for FUNIBER News Theme.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.funiberNavigation = {
    attach: function (context) {
      once('funiber-nav-toggle', '.nav-mobile-toggle', context).forEach(function (button) {
        const menu = document.querySelector('.nav-menu');
        if (!menu) return;

        button.addEventListener('click', function () {
          const isExpanded = button.getAttribute('aria-expanded') === 'true';
          button.setAttribute('aria-expanded', !isExpanded);
          menu.classList.toggle('is-open');
        });

        // Close menu on Escape key
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && menu.classList.contains('is-open')) {
            button.setAttribute('aria-expanded', 'false');
            menu.classList.remove('is-open');
            button.focus();
          }
        });
      });
    }
  };
})(Drupal, once);
