/**
* DO NOT EDIT THIS FILE.
* All changes should be applied to ./modules/text/text.es6.js
* See the following change record for more information,
* https://www.drupal.org/node/2873849
* @preserve
**/

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.textSummary = {
    attach: function attach(context, settings) {
      $(context).find('.js-text-summary').once('text-summary').each(function () {
        var $widget = $(this).closest('.js-text-format-wrapper');

        var $summary = $widget.find('.js-text-summary-wrapper');
        var $summaryLabel = $summary.find('label').eq(0);
        var $full = $widget.find('.js-text-full').closest('.js-form-item');
        var $fullLabel = $full.find('label').eq(0);

        if ($fullLabel.length === 0) {
          $fullLabel = $('<label></label>').prependTo($full);
        }

        var $link = $('<span class="field-edit-link"> (<button type="button" class="link link-edit-summary">' + Drupal.t('Hide summary') + '</button>)</span>');
        var $button = $link.find('button');
        var toggleClick = true;
        $link.on('click', function (e) {
          if (toggleClick) {
            $summary.hide();
            $button.html(Drupal.t('Edit summary'));
            $link.appendTo($fullLabel);
          } else {
            $summary.show();
            $button.html(Drupal.t('Hide summary'));
            $link.appendTo($summaryLabel);
          }
          e.preventDefault();
          toggleClick = !toggleClick;
        }).appendTo($summaryLabel);

        if ($widget.find('.js-text-summary').val() === '') {
          $link.trigger('click');
        }
      });
    }
  };
})(jQuery, Drupal);