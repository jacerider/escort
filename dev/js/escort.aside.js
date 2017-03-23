/**
 * @file
 * Attaches behaviors for Escort asides.
 */

(function ($, document, Drupal) {

  'use strict';

  function EscortAsides(wrapper) {
    this.$wrapper = $(wrapper);
    this.$trigger = this.$wrapper.find('.escort-aside-trigger');
    this.id = this.$trigger.data('escort-aside');
    this.display = this.$trigger.data('escort-aside-display');
    this.$content = $('#escort-ajax-' + this.id);
    this.usesAjax = typeof this.$trigger.data('escort-ajax') !== 'undefined';
    this.$document = $(document);
    this.$body = $('body');
    this.setup();
  }

  $.extend(EscortAsides, /** @lends Drupal.EscortAsides */{

    /**
     * Holds references to instantiated EscortAsides objects.
     *
     * @type {Array.<Drupal.EscortAsides>}
     */
    instances: []
  });

  $.extend(EscortAsides.prototype, /** @lends Drupal.EscortAsides# */{
    active: false,

    setup: function () {
      var _this = this;
      _this.ajax();
      _this.$trigger.on('click.aside', function (e) {
        e.preventDefault();
        _this.$trigger.off('click.aside.ajax');

        if (_this.active) {
          _this.hide();
        }
        else {
          _this.show();
        }
      });
    },

    ajax: function () {
      var _this = this;
      if (_this.usesAjax && typeof Drupal.ajax !== 'undefined') {
        // Bind Ajax behaviors to all items showing the class.
        _this.$trigger.once('aside-ajax').each(function () {
          var element_settings = {};
          element_settings.progress = {type: 'fullscreen'};

          // For anchor tags, these will go to the target of the anchor rather
          // than the usual location.
          element_settings.url = $(this).attr('href');
          element_settings.event = 'click.aside.ajax';
          element_settings.dialogType = $(this).data('dialog-type');
          element_settings.dialog = $(this).data('dialog-options');
          element_settings.base = $(this).attr('id');
          element_settings.element = this;
          Drupal.ajax(element_settings);
        });
      }
    },

    show: function (e) {
      var _this = this;
      if (!_this.active) {
        _this.active = true;
        _this.$wrapper.addClass('escort-active');
        _this.$content.addClass('escort-active');
        // Bind body click event.
        setTimeout(function () {
          // React to clicking on the body.
          _this.$document.on('click.escort-aside', function (e) {
            if (!$(e.target).closest('.escort-aside-content').length) {
              _this.hide();
            }
          });
          // React to region visibility.
          _this.$document.on('escort-region:show escort-region:hide', function (e, $region) {
            _this.hide();
          });
        }, 10);
      }
    },

    hide: function (e) {
      var _this = this;
      if (_this.active) {
        _this.active = false;
        _this.$wrapper.removeClass('escort-active');
        _this.$content.removeClass('escort-active');
        _this.$document.off('click.escort-aside');
        _this.$document.off('escort-region:show escort-region:hide');
      }
    }
  });

  Drupal.behaviors.escortAside = {
    attach: function (context) {
      var $escortAsides = $(context).find('.escort-aside').once('escort-aside');
      if ($escortAsides.length) {
        for (var i = 0; i < $escortAsides.length; i++) {
          EscortAsides.instances.push(new EscortAsides($escortAsides[i]));
        }
      }
    }
  };

  // Expose constructor in the public space.
  Drupal.EscortAsides = EscortAsides;

}(jQuery, document, Drupal));
