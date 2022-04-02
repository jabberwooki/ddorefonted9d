/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ddo_secondary_menu = {
    attach: function (context, settings) {
      console.log(`Dans secondary_menu.js`);
      // ajout d'un classe au niveau de la navigation pour indiquer si aucun lien n'est actif
      $("#page-nav-left nav.navigation").each(function () {
        if (!$("a.is-active", $(this)).length) {
          console.log("je suis dans un menu qui a des liens inactifs");
          console.log($(this).attr('id'));
          $(this).addClass("nav-inactive");
        }
      });

    }
  };

})(jQuery, Drupal);
