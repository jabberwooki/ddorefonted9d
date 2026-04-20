/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ddo_secondary_menu = {
    attach: function (context, settings) {
      // ajout d'un classe au niveau de la navigation pour indiquer si aucun lien n'est actif
      $("#page-nav-left nav.navigation").each(function () {
        if (!$("a.is-active", $(this)).length) {
          $(this).addClass("nav-inactive");
        }
      });

    }
  };

})(jQuery, Drupal);
