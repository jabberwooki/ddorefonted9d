/**
 * @file
 * Global utilities.
 *
 */
(function($, Drupal) {

  'use strict';

  Drupal.behaviors.ddo_content_form_alter = {
    attach: function(context, settings) {

      // Par défaut, le texte d'aide sur les formats HTML est visible pour un champ de type text simple formatté.
      // Suppression du texte d'aide visible sous le champ Tarif (Spectacle).
      $('#edit-field-price-0-format-guidelines').hide();
      // Suppression du texte d'aide visible sous le champ Liens plus d'infos (Article et Evénement).
      $('#edit-field-more-infos-link-0-format-guidelines').hide();

    }
  };

})(jQuery, Drupal);
