/**
 * @file
 * calendar
 *
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.ddo_technique = {
    attach: function (context, settings) {

      $(document, context).once('ddo_technique').each(function () {
        console.log("dans technique.js");
        console.log("Suppresion des paragraphes documents !");
        $(".paragraph--type--documents").parents(".custom-paragraph").addClass("open-close");
        $(".open-close .field--name-field-document-sets").hide();
        $(".open-close .layout--onecol .field--name-field-title").click(function() {
          $(this).toggleClass("open");
          $(this).next(".field--name-field-document-sets").slideToggle();
        })
      });
    }
  }
}(jQuery, Drupal, drupalSettings));
