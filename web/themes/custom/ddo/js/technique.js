/**
 * @file
 * calendar
 *
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.ddo_technique = {
    attach: function (context, settings) {
      $(document, context).each(function () {
        $(".paragraph--type--documents")
          .parents(".custom-paragraph")
          .addClass("open-close");

        $(".open-close .field--name-field-document-sets").hide();

        $(".open-close .layout--onecol .field--name-field-title").click(
          function () {
            $(this).toggleClass("open");

            if ($(this).hasClass("open")) {
              $(this).next(".field--name-field-document-sets").show();
            } else {
              $(this).next(".field--name-field-document-sets").hide();
            }
          }
        );
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
