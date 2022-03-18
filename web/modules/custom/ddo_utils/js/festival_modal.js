/**
 * @file
 * Crée la fenêtre modale pour les festivals passés ou en cours de programmation.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.festivalModal = {
    attach: function (context, settings) {
      // console.log('dans festival_modal.js');

      // alert('test');
      // confirm("Press OK to confirm!");

      $('#festivalModal').modal('show');
    }
  };
})(jQuery);
