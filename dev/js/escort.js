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
    full: false,

    setup: function () {
      var _this = this;

      // Attach events.
      switch (_this.event) {
        case 'click':
          _this.$trigger.click(function (e) {
            e.preventDefault();
            if (_this.full) {
              _this.hideFull();
            }
            else {
              _this.showFull();
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
          _this.$trigger.click(function (e) {
            if (_this.full) {
              _this.hideFull();
            }
            else {
              _this.showFull();
            }
          });
          _this.$region.hover(function (e) {
            e.preventDefault();
            _this.showFull();

            // Bind body click event.
            _this.$body.on('click.escort-' + _this.region, function (e) {
              if (_this.full && !$(e.target).closest(_this.$region).length) {
                _this.hideFull();
              }
            });
          });
          break;
      }
    },

    showMini: function (e) {
      if (!this.mini) {
        this.mini = true;
        this.$body.addClass('show-escort-mini-' + this.region);
      }
    },

    hideMini: function (e) {
      if (this.mini) {
        this.mini = false;
        this.$body.removeClass('show-escort-mini-' + this.region);
      }
    },

    showFull: function () {
      if (!this.full) {
        this.full = true;
        this.$body.addClass('show-escort-full-' + this.region);
      }
    },

    hideFull: function () {
      if (this.full) {
        this.full = false;
        this.$body.removeClass('show-escort-full-' + this.region);
        this.$body.off('click.escort-' + this.region);
      }
    }
  });

  Drupal.behaviors.escort = {
    attach: function (context) {
      var $escrotRegionToggles = $(context).find('.escort-toggle').once('escort-toggle').addClass('escort-toggle-processed');
      if ($escrotRegionToggles.length) {
        for (var i = 0; i < $escrotRegionToggles.length; i++) {
          EscortRegionToggles.instances.push(new EscortRegionToggles($escrotRegionToggles[i]));
        }
        setTimeout(function () {
          $('body').addClass('escort-ready');
        }, 10);
      }
    }
  };

  // Expose constructor in the public space.
  Drupal.EscortRegionToggles = EscortRegionToggles;

}(jQuery, document));
