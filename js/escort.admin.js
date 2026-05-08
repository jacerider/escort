/**
 * @file
 * Escort admin behaviors. Reorders escort items between regions using
 * SortableJS and POSTs the new region/weight map back to the server.
 */

(function (Drupal, drupalSettings, once) {

  'use strict';

  Drupal.behaviors.escortAdminSort = {
    attach: function (context) {
      var newContainers = once('escort-admin-sort', '.escort-sort', context);
      if (!newContainers.length || typeof window.Sortable === 'undefined') {
        return;
      }

      var allContainers = document.querySelectorAll('.escort-sort');
      var updating = false;

      function updateEscorts() {
        if (updating) {
          return;
        }
        updating = true;

        var escortValues = {};
        allContainers.forEach(function (container) {
          var regionId = container.getAttribute('data-escort-region');
          var items = container.querySelectorAll('.escort-sortable');
          items.forEach(function (item, index) {
            var id = item.getAttribute('data-escort-id');
            if (id) {
              escortValues[id] = { region: regionId, weight: index };
            }
          });
        });

        fetch(Drupal.url('admin/config/user-interface/escort/update'), {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(escortValues)
        });

        setTimeout(function () { updating = false; }, 10);
      }

      newContainers.forEach(function (container) {
        window.Sortable.create(container, {
          group: 'escort-admin',
          draggable: '.escort-sortable',
          handle: '.escort-item',
          ghostClass: 'escort-placeholder',
          animation: 150,
          onStart: function () {
            document.body.classList.add('escort-sorting');
          },
          onEnd: function () {
            document.body.classList.remove('escort-sorting');
          },
          onSort: updateEscorts
        });
      });
    }
  };

})(Drupal, drupalSettings, once);
