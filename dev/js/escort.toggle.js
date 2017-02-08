/**
 * @file
 * Global Escort javascript.
 */

(function ($, document) {

  'use strict';

  function EscortRegionToggles(trigger) {
    this.$trigger = $(trigger);
    this.region = this.$trigger.data('region');
    this.event = this.$trigger.data('event');
    this.$region = $('#escort-' + this.region);
    this.$body = $('body');
    this.setup();
  }

  $.extend(EscortRegionToggles, /** @lends Drupal.EscortRegionToggles */{

    /**
     * Holds references to instantiated EscortRegionToggles objects.
     *
     * @type {Array.<Drupal.EscortRegionToggles>}
     */
    instances: []
  });

  $.extend(EscortRegionToggles.prototype, /** @lends Drupal.EscortRegionToggles# */{
    lock: false,
    mini: false,

    setup: function () {
      var _this = this;

      _this.$region.addClass('escort-instant');

      // Attach events.
      switch (_this.event) {
        case 'click':
          _this.$trigger.click(function (e) {
            e.preventDefault();
            _this.showMini();
          });
          _this.$trigger.on('mouseleave', function (e) {
            if (_this.mini) {
              _this.hideMini();
            }
          });
          break;
        default:
          // On hover is default state.
          _this.$trigger.hover(function (e) {
            e.preventDefault();
            _this.showMini();
          }, function (e) {
            e.preventDefault();
            _this.hideMini();
          });
          break;
      }
    },

    showMini: function (e) {
      var _this = this;
      if (!_this.mini) {
        _this.mini = true;
        _this.$body.addClass('show-escort-mini-' + _this.region);
        _this.$body.trigger('escort-toggle-mini:show', [_this.$region]);
      }
    },

    hideMini: function (e) {
      var _this = this;
      if (_this.mini) {
        _this.mini = false;
        _this.$body.removeClass('show-escort-mini-' + _this.region);
        _this.$body.trigger('escort-region-mini:hide', [_this.$region]);
      }
    }
  });

  Drupal.behaviors.escortToggle = {
    attach: function (context) {
      var $escrotRegionToggles = $(context).find('.escort-toggle').once('escort-toggle').addClass('escort-toggle-processed');
      if ($escrotRegionToggles.length) {
        for (var i = 0; i < $escrotRegionToggles.length; i++) {
          EscortRegionToggles.instances.push(new EscortRegionToggles($escrotRegionToggles[i]));
        }
      }
    }
  };

  // Expose constructor in the public space.
  Drupal.EscortRegionToggles = EscortRegionToggles;

}(jQuery, document));
