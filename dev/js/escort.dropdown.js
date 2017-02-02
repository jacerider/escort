/**
 * @file
 * Attaches behaviors for Escort dropdowns.
 */

(function ($, document, Drupal) {

  'use strict';

  function EscortDropdowns(wrapper) {
    this.$wrapper = $(wrapper);
    this.$trigger = this.$wrapper.find('.escort-dropdown-trigger');
    this.usesAjax = typeof this.$trigger.data('escort-ajax') !== 'undefined';
    this.$body = $('body');
    this.setup();
  }

  $.extend(EscortDropdowns, /** @lends Drupal.EscortDropdowns */{

    /**
     * Holds references to instantiated EscortDropdowns objects.
     *
     * @type {Array.<Drupal.EscortDropdowns>}
     */
    instances: []
  });

  $.extend(EscortDropdowns.prototype, /** @lends Drupal.EscortDropdowns# */{
    active: false,

    setup: function () {
      var _this = this;
      _this.ajax();
      _this.$trigger.on('click.dropdown', function (e) {
        e.preventDefault();
        _this.$trigger.off('click.dropdown.ajax');

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
        _this.$trigger.once('dropdown-ajax').each(function () {
          var element_settings = {};
          element_settings.progress = {type: 'fullscreen'};

          // For anchor tags, these will go to the target of the anchor rather
          // than the usual location.
          element_settings.url = $(this).attr('href');
          element_settings.event = 'click.dropdown.ajax';
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
        // Bind body click event.
        setTimeout(function () {
          // React to clicking on the body.
          _this.$body.on('click.escort-dropdown', function (e) {
            if (!$(e.target).closest('.escort-dropdown-content').length) {
              _this.hide();
            }
          });
          // React to region visibility.
          _this.$body.on('escort-region:show escort-region:hide', function (e, $region) {
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
        _this.$body.off('click.escort-dropdown');
        _this.$body.off('escort-region:show escort-region:hide');
      }
    }
  });

  Drupal.behaviors.escortDropdown = {
    attach: function (context) {
      var $escortDropdowns = $(context).find('.escort-dropdown').once('escort-dropdown');
      if ($escortDropdowns.length) {
        for (var i = 0; i < $escortDropdowns.length; i++) {
          EscortDropdowns.instances.push(new EscortDropdowns($escortDropdowns[i]));
        }
      }
    }
  };

  // Expose constructor in the public space.
  Drupal.EscortDropdowns = EscortDropdowns;

}(jQuery, document, Drupal));
