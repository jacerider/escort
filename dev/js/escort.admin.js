/**
 * @file
 * Escort admin behaviors.
 */

(function ($, window, Drupal) {

  'use strict';

  Drupal.behaviors.escortAdminSort = {
    attach: function (context, settings) {
      var $sortAll = $('.escort-sort', context);
      var $sort = $sortAll.once('escort-admin-sort');
      var updating = false;

      // Update region and weights of all escorts.
      function updateEscorts(event, ui) {
        if (!updating) {
          updating = true;
          var escortValues = {};
          var regionId;
          $sortAll.each(function () {
            regionId = $(this).data('escort-region');
            $(this).find('.escort-sortable').each(function (key) {
              escortValues[$(this).data('escort-id')] = {
                region: regionId,
                weight: key
              };
            });
          });

          $.ajax({
            url: Drupal.url('admin/config/escort/update'),
            type: 'POST',
            data: JSON.stringify(escortValues),
            dataType: 'json',
            success: function (results) {
              // Success
            }
          });

          // This function can be called multiple times if an escort is moved
          // to a new region. Since we are processing all items we set a small
          // timeout to prevent multiple unnecessary calls.
          setTimeout(function () {
            updating = false;
          }, 10);
        }
      }

      if ($sort.length) {
        $sort.sortable({
          items: '.escort-sortable',
          connectWith: '.escort-sort',
          placeholder: 'escort-placeholder',
          // forcePlaceholderSize: true,
          opacity: 0.5,
          scroll: false,
          start: function () {
            $('body').addClass('escort-sorting');
          },
          stop: function () {
            $('body').removeClass('escort-sorting');
          },
          update: updateEscorts
        }).disableSelection();
      }

    }
  };

})(jQuery, window, Drupal);
