/**
* DO NOT EDIT THIS FILE.
* All changes should be applied to ./modules/color/preview.es6.js
* See the following change record for more information,
* https://www.drupal.org/node/2873849
* @preserve
**/

(function ($, Drupal) {

  'use strict';

  Drupal.color = {
    callback: function callback(context, settings, form, farb, height, width) {
      var accum;
      var delta;

      form.find('.color-preview').css('backgroundColor', form.find('.color-palette input[name="palette[base]"]').val());

      form.find('#text').css('color', form.find('.color-palette input[name="palette[text]"]').val());
      form.find('#text a, #text h2').css('color', form.find('.color-palette input[name="palette[link]"]').val());

      function gradientLineColor(i, element) {
        for (var k in accum) {
          if (accum.hasOwnProperty(k)) {
            accum[k] += delta[k];
          }
        }
        element.style.backgroundColor = farb.pack(accum);
      }

      var color_start;
      var color_end;
      for (var i in settings.gradients) {
        if (settings.gradients.hasOwnProperty(i)) {
          color_start = farb.unpack(form.find('.color-palette input[name="palette[' + settings.gradients[i].colors[0] + ']"]').val());
          color_end = farb.unpack(form.find('.color-palette input[name="palette[' + settings.gradients[i].colors[1] + ']"]').val());
          if (color_start && color_end) {
            delta = [];
            for (var j in color_start) {
              if (color_start.hasOwnProperty(j)) {
                delta[j] = (color_end[j] - color_start[j]) / (settings.gradients[i].vertical ? height[i] : width[i]);
              }
            }
            accum = color_start;

            form.find('#gradient-' + i + ' > div').each(gradientLineColor);
          }
        }
      }
    }
  };
})(jQuery, Drupal);