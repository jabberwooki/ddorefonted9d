/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ddo_search = {
    attach: function (context, settings) {
        console.log(`Dans search.js`);
        $("#custom-search-button").on("click", function(){
          console.log(`Click sur la loupe`);
          $("#edit-keys").focus();
          $("#search-row").toggleClass("visually-hidden-focusable");
          if($("#search-row").hasClass("visually-hidden-focusable")) {
            document.activeElement.blur();
          } else {
            $("#edit-keys").focus();
          }
        });
        $("#close-search-form").on("click", function(){
          document.activeElement.blur();
          $("#search-row").toggleClass("visually-hidden-focusable");
        });

    }
  };

})(jQuery, Drupal);
