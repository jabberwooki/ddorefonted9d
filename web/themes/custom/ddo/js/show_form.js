/**
 * @file
 * Global utilities.
 *
 */
(function($, Drupal) {

  'use strict';

  Drupal.behaviors.ddo_show_form = {
    attach: function(context, settings) {

      // Suppression du texte d'aide visible sous le champ Tarif
      // Par défaut, il est visible pour un champ de type text simple formatté.
      $('#edit-field-price-0-format-guidelines').hide();

    }
  };

})(jQuery, Drupal);
