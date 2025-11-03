((Drupal, settings) => {
  'use strict';

  Drupal.behaviors.iframeResizer = {
    attach: context => {
      iframeResize({
        'license': '1jy4dww5qzv-s54r73oxcn-v59f4kfgfz',
      }, '.iframe-resize');
    }
  };

})(Drupal, drupalSettings);
