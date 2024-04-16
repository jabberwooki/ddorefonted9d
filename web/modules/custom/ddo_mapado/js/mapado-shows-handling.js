/**
 * @file
 * Gère le comportement du champ Réservation d'un spectacle
 */

(function ($) {
  "use strict";

  Drupal.behaviors.shows = {
    attach: function (context, settings) {
      if (
        $(
          '#edit-field-reservation--wrapper input[type="radio"]:checked'
        ).val() != 7
      ) {
        $("#reservation-warning-message").hide();
      }

      $('#edit-field-reservation--wrapper input[type="radio"]').click(
        function () {
          if ($(this).val() == 7) {
            $("#reservation-warning-message").show();
          } else {
            $("#reservation-warning-message").hide();
          }
        }
      );
    },
  };
})(jQuery);
