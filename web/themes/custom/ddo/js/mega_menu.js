/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ddo_mega_menu = {
    attach: function (context, settings) {
      console.log(`Dans mega_menu.js`);
      $("#mega-menu").hide(0);

      // Gestion du click sur le bouton burger menu
      $("#custom-mega-menu-button").on("click", function (event) {
        console.log(`Click sur le burger menu`);
        $("#custom-mega-menu-button").toggleClass("open-menu-button close-menu-button");
        $("#mega-menu").slideToggle("1500");
        event.stopImmediatePropagation();// Astuce de Christophe pour que l'événement ne soit géré qu'une fois
      });
    }
  };

})(jQuery, Drupal);
