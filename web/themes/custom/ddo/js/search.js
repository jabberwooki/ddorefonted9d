/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ddo_search = {
    attach: function (context, settings) {

      /* Ne fonctionne pas voir si on pourrait charger https://github.com/robloach/jquery-once
      $('#edit-keys', context).once('ddo', function () {
        // Code here will only be applied to $('#some_element')
        // a single time.
        console.log(`ONCE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!`);
      });*/
      console.log(`Dans search.js !!!`);
      $("#close-search-form").on("click", function (event) {
        /*console.log(`Click sur la croix de suppression de la zone de recherche`);*/
        document.activeElement.blur();
        $("#search-row").toggleClass("visually-hidden-focusable");
        event.stopImmediatePropagation();// Astuce de Christophe pour que l'événement ne soit géré qu'une fois
      });
      $("#custom-search-button").on("click", function (event) {
        /*console.log(`Click sur la loupe`);*/
        if ($("#search-row").hasClass("visually-hidden-focusable")) {
          $("#search-row").removeClass("visually-hidden-focusable");
          $("#edit-keys").focus();
        } else {
          $("#search-row").addClass("visually-hidden-focusable");
          document.activeElement.blur();
        }
        event.stopImmediatePropagation();// Astuce de Christophe pour que l'événement ne soit géré qu'une fois
      });


    }
  };

})(jQuery, Drupal);
