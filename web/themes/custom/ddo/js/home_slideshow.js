/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ddo = {
    attach: function (context, settings) {
      $(document, context).once('ddo').each( function() {

        console.log(`Dans home_slideshow.js`);
        const nb_slides = $("#custom-home-slideshow .view-content > .views-row").length;
        if(nb_slides && nb_slides > 1) {
          const ss_width = parseInt($("#custom-home-slideshow").width() + 1);
          const all_slides = $("#custom-home-slideshow .view-content");
          // Je n'arrive pas à récupéer la hauteur, alors je joue avec les proportions pour l'avoir...
          let img_height = parseInt(($("#custom-home-slideshow .field__item").first().width() / 1.439534884) + 1) ;

          all_slides.width(ss_width * nb_slides);
          $("#custom-home-slideshow .view-content > .views-row").width(ss_width);

          // Création des boutons pour avancer ou reculer
          const control_buttons = $(`<div id="slideshow-control"></div>`).appendTo("#custom-home-slideshow");
          const previous_button = $(`<div id="ss-previous" class="control-button"><span class="visually-hidden-focusable">Prédédent</span></div>`).appendTo(control_buttons);
          const next_button = $(`<div id="ss-next" class="control-button"><span class="visually-hidden-focusable">Prédédent</span></div>`).appendTo(control_buttons);
          control_buttons.css( "top",`${img_height + 8}px`);

          // Gestion des clics sur les boutons
          previous_button.on("click", function(){
            console.log(`Clic sur le bouton précédent`);
            $("#custom-home-slideshow .views-row:last").prependTo(all_slides);
            all_slides.animate({
              left: `-=${ss_width}px`,
            }, 0 );
            all_slides.animate({
              left: `+=${ss_width}px`,
            }, 1000 );
          })
          next_button.on("click", function(){
            console.log(`Clic sur le bouton suivant`);
            all_slides.animate({
              left: `-=${ss_width}px`,
            }, 1000, function(){
              all_slides.animate({
                left: `+=${ss_width}px`,
              }, 0 );
              $("#custom-home-slideshow .views-row:first").appendTo(all_slides);
            });


          })
        }

      });
    }
  };

})(jQuery, Drupal);
