/**
 * @file
 * Main theme behaviors and interactive features.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.funiberMain = {
    attach: function (context) {
      // Newsletter mock submit handler
      once('funiber-newsletter', '.newsletter-form', context).forEach(function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          const input = form.querySelector('.newsletter-input');
          if (input && input.value) {
            alert('¡Gracias por suscribirte al boletín de noticias tecnológicas y educativas de FUNIBER!');
            input.value = '';
          }
        });
      });

      // Search bar form focus handler
      once('funiber-search', '.search-form', context).forEach(function (form) {
        form.addEventListener('submit', function (e) {
          const input = form.querySelector('.search-input');
          if (!input || !input.value.trim()) {
            e.preventDefault();
            input.focus();
          }
        });
      });
    }
  };
})(Drupal, once);
