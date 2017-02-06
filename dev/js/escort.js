/**
 * @file
 * Global Escort javascript.
 */

(function ($, document) {

  'use strict';

  function Escort(region) {
    this.$region = $(region);
    this.region = this.$region.data('region');
    this.$body = $('body');
    this.setup();
  }

  $.extend(Escort, /** @lends Drupal.Escort */{

    /**
     * Holds references to instantiated Escort objects.
     *
     * @type {Array.<Drupal.Escort>}
     */
    instances: []
  });

  $.extend(Escort.prototype, /** @lends Drupal.Escort# */{
    full: false,

    setup: function () {
      var _this = this;

      // Remove empty.
      if (!_this.$region.find('.escort-item').length) {
        _this.$region.remove();
        _this.$body.removeClass('has-escort-' + this.region);
      }
      else {
        // Vertical display.
        _this.$region.filter('.escort-vertical').on('mouseenter', function (e) {
          e.preventDefault();
          _this.showFull();
        });
      }
    },

    showFull: function () {
      var _this = this;
      if (!_this.full) {
        _this.full = true;
        _this.$body.addClass('show-escort-full-' + _this.region);
        // Bind body click event.
        _this.$body.on('click.escort-' + _this.region, function (e) {
          if (_this.full && !$(e.target).closest(_this.$region).length) {
            _this.hideFull();
          }
        });
        _this.$body.trigger('escort-region:show');
      }
    },

    hideFull: function () {
      var _this = this;
      if (_this.full) {
        _this.full = false;
        _this.$body.removeClass('show-escort-full-' + _this.region);
        _this.$body.off('click.escort-' + _this.region);
        _this.$body.trigger('escort-region:hide', [_this.$region]);
      }
    }
  });

  Drupal.behaviors.escort = {
    attach: function (context) {
      var $escortRegion = $(context).find('.escort-region').once('escort-region').addClass('escort-region-processed');
      if ($escortRegion.length) {
        for (var i = 0; i < $escortRegion.length; i++) {
          Escort.instances.push(new Escort($escortRegion[i]));
        }
        setTimeout(function () {
          $('body').addClass('escort-ready');
        }, 10);
      }
    }
  };

  // Expose constructor in the public space.
  Drupal.Escort = Escort;

}(jQuery, document));
