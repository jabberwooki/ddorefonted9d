/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ddo_slideshow = {
    attach: function (context, settings) {
      // console.log(`Dans home_slideshow !!!`);
      const nb_slides = $("#custom-home-slideshow .view-content > .views-row").length;
      if (nb_slides && nb_slides > 1) {
        let ss_width = parseInt($("#custom-home-slideshow").width() + 1);
        const all_slides = $("#custom-home-slideshow .view-content");
        // Je n'arrive pas à récupéer la hauteur, alors je joue avec les proportions pour l'avoir...
        let img_height = parseInt((ss_width / 1.439534884) + 1);
        all_slides.width(ss_width * nb_slides);
        $("#custom-home-slideshow .view-content > .views-row").width(ss_width);

        // Création des boutons pour avancer ou reculer
        const control_buttons = $(`<div id="slideshow-control"></div>`).appendTo("#custom-home-slideshow");
        const previous_button = $(`<div id="ss-previous" class="control-button"><span class="visually-hidden-focusable">Prédédent</span></div>`).appendTo(control_buttons);
        const next_button = $(`<div id="ss-next" class="control-button"><span class="visually-hidden-focusable">Prédédent</span></div>`).appendTo(control_buttons);
        control_buttons.css("top", `${img_height + 8}px`);
        const slide_titles = $(".zoom-img-and-title h3");

        // Positionnement du titre
        // slide_titles.each(function () {
        //   $(this).css("top", `${img_height - 53}px`);
        // })
        positionTitleSpans(img_height, slide_titles);

        // Gestion des clics sur les boutons
        previous_button.on("click", function () {
          $("#custom-home-slideshow .views-row:last").prependTo(all_slides);
          all_slides.animate({
            left: `-=${ss_width}px`,
          }, 0);
          all_slides.animate({
            left: `+=${ss_width}px`,
          }, 1000);
        })
        next_button.on("click", function () {
          all_slides.animate({
            left: `-=${ss_width}px`,
          }, 1000, function () {
            all_slides.animate({
              left: `+=${ss_width}px`,
            }, 0);
            $("#custom-home-slideshow .views-row:first").appendTo(all_slides);
          });


        })

        // Gestion du redimenssionnement
        $( window ).resize(function() {
          console.log(`Gestion du resize`);
          ss_width = parseInt($("#custom-home-slideshow").width() + 1);
          img_height = parseInt(($("#custom-home-slideshow .field__item").first().width() / 1.439534884) + 1);
          all_slides.width(ss_width * nb_slides);
          $("#custom-home-slideshow .view-content > .views-row").width(ss_width);

          // slide_titles.each(function () {
          //   $(this).css("top", `${img_height - 53}px`);
          // })
          positionTitleSpans(img_height, slide_titles);
        });
      }

      function positionTitleSpans(img_height, slide_titles) {
        slide_titles.each(function () {
          // $(this).css("top", `${img_height - 53}px`);
          let title_span = $(this).find('span');
          let span_height = parseInt(title_span.height());
          let title_span_padding_top =  parseInt(title_span.css('padding-top'));
          // console.log(img_height);
          // console.log(span_height);
          // console.log(title_span_padding_top);
          $(this).css("top", `${img_height - (span_height + title_span_padding_top)}px`);
        })
      }
    }
  };

})(jQuery, Drupal);
