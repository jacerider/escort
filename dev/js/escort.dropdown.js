/**
 * @file
 * Attaches behaviors for Escort dropdowns.
 */

(function ($, document) {

  'use strict';

  function EscortDropdowns(trigger) {
    this.$trigger = $(trigger);
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

    setup: function () {
      var _this = this;
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

}(jQuery, document));
