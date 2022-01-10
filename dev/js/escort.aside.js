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
    this.dialogType = this.$trigger.data('dialog-type');
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
    usedAjax: false,

    setup: function () {
      var _this = this;
      _this.ajax();
      _this.$trigger.on('click.aside', function (e) {
        e.preventDefault();

        if (_this.active) {
          _this.hide();
        }
        else {
          // Only unbind if link not handled by Drupal modal.
          if (_this.dialogType) {
            Drupal.Escort.hideExpanded();
          }
          else {
            _this.$trigger.off('click.aside.ajax');
          }
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
          element_settings.progress = {type: 'fullscreen'};
          if (_this.dialogType) {
            element_settings.dialogType = _this.dialogType;
            element_settings.dialog = $(this).data('dialog-options');
          }
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
        if (_this.usesAjax && !_this.usedAjax) {
          // The ajax click event prevents the document click from firing which
          // keeps other asides open. We trigger the click manually.
          _this.$document.trigger('click.escort-aside');
          _this.usedAjax = true;
        }
        _this.$wrapper.addClass('escort-active');
        _this.$content.addClass('escort-active');
        // Bind body click event.
        setTimeout(function () {
          // React to clicking on the body.
          _this.$document.on('click.escort-aside.' + _this.id, function (e) {
            if (!$(e.target).closest('.escort-aside-content').length) {
              _this.hide();
            }
          });
          // React to region visibility.
          _this.$document.on('escort-region:show.' + _this.id + ' escort-region:hide.' + _this.id, function (e, $region) {
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
        _this.$document.off('click.escort-aside.' + _this.id);
        _this.$document.off('escort-region:show.' + _this.id + ' escort-region:hide.' + _this.id);
      }
    }
  });

  Drupal.behaviors.escortAside = {
    attach: function (context) {
      var $escortAsides = $('.escort-aside').once('escort-aside');
      if ($escortAsides.length) {
        for (var i = 0; i < $escortAsides.length; i++) {
          EscortAsides.instances.push(new EscortAsides($escortAsides[i]));
        }
      }

      this.updateDestinations(context);
    },

    updateDestinations: function (context) {
      var url;
      var destination = drupalSettings.path.baseUrl + drupalSettings.path.currentPath;

      function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');
        // var separator = uri.indexOf('?') !== -1 ? '&' : '?';
        if (uri.match(re)) {
          return uri.replace(re, '$1' + key + '=' + value + '$2');
        }
        else {
          return uri;
          // return uri + separator + key + '=' + value;
        }
      }

      $('.escort-aside-content a').once('escort-aside-content').each(function () {
        url = $(this).attr('href');
        url = updateQueryStringParameter(url, 'destination', destination);
        $(this).attr('href', url);
      });
    }
  };

  // Expose constructor in the public space.
  Drupal.EscortAsides = EscortAsides;

}(jQuery, document, Drupal));
