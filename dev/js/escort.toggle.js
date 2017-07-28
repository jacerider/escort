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
    if (this.$region.length) {
      this.$document = $(document);
      this.$body = $('body');
      this.setup();
    }
    else {
      this.$trigger.remove();
    }
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
    active: false,

    setup: function () {
      var _this = this;

      _this.$region.addClass('escort-instant');

      _this.$trigger.on('click', function (e) {
        e.preventDefault();
        if (_this.active) {
          _this.hideMini();
        }
        else {
          _this.showMini();
        }
      });

      // Attach trigger events.
      switch (_this.event) {
        case 'hover':
          // On hover is default state.
          _this.$trigger.hover(function (e) {
            e.preventDefault();
            _this.showMini();
          }, function (e) {
            e.preventDefault();
            _this.hideMini();
          });

          // Attach region events.
          _this.$region.on('escort-region-expanded:show', function () {
            _this.$trigger.addClass('is-active');
          }).on('escort-region-expanded:hide', function () {
            _this.$trigger.removeClass('is-active');
          });
          break;
      }
    },

    showMini: function (e) {
      var _this = this;
      if (!_this.active) {
        _this.active = true;
        _this.$body.addClass('show-escort-mini-' + _this.region);
        _this.$body.trigger('escort-toggle-mini:show', [_this.$region]);

        // Bind body click event.
        if (_this.event === 'click') {
          _this.$trigger.addClass('is-active');
          setTimeout(function () {
            _this.$document.on('click.escort-mini-' + _this.region, function (e) {
              if (_this.active && !$(e.target).closest(_this.$region).length) {
                _this.hideMini();
              }
            });
          }, 10);
        }
      }
    },

    hideMini: function (e) {
      var _this = this;
      if (_this.active) {
        _this.active = false;
        _this.$body.removeClass('show-escort-mini-' + _this.region);
        _this.$body.trigger('escort-region-mini:hide', [_this.$region]);

        if (_this.event === 'click') {
          _this.$trigger.removeClass('is-active');
          _this.$document.off('click.escort-mini-' + _this.region);
        }
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
