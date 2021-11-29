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
      // Place l'élément du main menu concernant le ddo en deuxième colonne
      $("#block-main-menu-in-mega ul.nav > li#li-mega-menu-5").appendTo("#col-2-mega-menu > ul");

      // Place les menus bas en troisième colonne
      $("#block-footer-menu-1-in-mega").appendTo("#col-3-mega-menu");
      $("#block-footer-menu-2-in-mega").appendTo("#col-3-mega-menu");

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
