/**
 * @file
 * Global Escort javascript.
 */

(function ($, Drupal, displace) {

  'use strict';

  function Escort(region) {
    this.$region = $(region);
    this.region = this.$region.data('region');
    this.$document = $(document);
    this.$body = $('body');
    this.setup();
  }

  $.extend(Escort, /** @lends Drupal.Escort */{

    /**
     * Holds references to instantiated Escort objects.
     *
     * @type {Array.<Drupal.Escort>}
     */
    instances: [],

    /**
     * Hide full version of all instances.
     */
    hideFull: function () {
      for (var i = 0, len = this.instances.length; i < len; i++) {
        this.instances[i].hideFull();
      }
    }
  });

  $.extend(Escort.prototype, /** @lends Drupal.Escort# */{
    active: false,

    setup: function () {
      var _this = this;

      // Remove empty.
      if (!_this.$region.find('.escort-item').length) {
        _this.$region.remove();
        _this.$body.removeClass('has-escort-' + this.region);
        Drupal.displace(true);
      }
      else {
        var timeout;
        var timeoutDelay;
        // Vertical display.
        _this.$region.filter('.escort-vertical').on('mouseenter.escort', function (e) {
          e.preventDefault();
          timeoutDelay = _this.$region.hasClass('escort-instant') ? 0 : 300;
          timeout = setTimeout(function () {
            _this.showFull();
          }, timeoutDelay);
        }).on('mouseleave.escort', function (e) {
          e.preventDefault();
          clearTimeout(timeout);
        });
      }
    },

    showFull: function () {
      var _this = this;
      if (!_this.active) {
        _this.active = true;
        _this.$body.addClass('show-escort-full-' + _this.region);
        // Bind body click event.
        _this.$document.on('click.escort-' + _this.region, function (e) {
          if (_this.active && !$(e.target).closest(_this.$region).length) {
            _this.hideFull();
          }
        });
        _this.$region.trigger('escort-region-full:show', [_this.$region]);
        _this.$document.trigger('escort-region:show', [_this.$region]);
      }
    },

    hideFull: function () {
      var _this = this;
      if (_this.active) {
        _this.active = false;
        _this.$body.removeClass('show-escort-full-' + _this.region);
        _this.$document.off('click.escort-' + _this.region);
        _this.$region.trigger('escort-region-full:hide', [_this.$region]);
        _this.$document.trigger('escort-region:hide', [_this.$region]);
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

}(jQuery, Drupal, Drupal.displace));
