/**
* DO NOT EDIT THIS FILE.
* All changes should be applied to ./modules/ckeditor/js/models/Model.es6.js
* See the following change record for more information,
* https://www.drupal.org/node/2873849
* @preserve
**/

(function (Drupal, Backbone) {

  'use strict';

  Drupal.ckeditor.Model = Backbone.Model.extend({
    defaults: {
      activeEditorConfig: null,

      $textarea: null,

      isDirty: false,

      hiddenEditorConfig: null,

      buttonsToFeatures: null,

      featuresMetadata: null,

      groupNamesVisible: false
    },

    sync: function sync() {
      this.get('$textarea').val(JSON.stringify(this.get('activeEditorConfig')));
    }
  });
})(Drupal, Backbone);