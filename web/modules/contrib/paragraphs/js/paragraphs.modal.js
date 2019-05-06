/**
 * @file paragraphs.modal.js
 *
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Click handler for click "Add" button between paragraphs.
   *
   * @type {Object}
   */
  Drupal.behaviors.paragraphsModalAdd = {
    attach: function (context) {
      $('.paragraph-type-add-modal-button', context)
        .once('add-click-handler')
        .on('click', function (event) {
          var $button = $(this);
          var $add_more_wrapper = $button.parent().siblings('.paragraphs-add-dialog');
          var delta = '';

          // If it's not initial button, then it can be add in between button.
          if ($add_more_wrapper.length === 0) {
            $add_more_wrapper = $button.closest('table').siblings('.clearfix').find('.paragraphs-add-dialog');
            delta = $button.closest('tr').index() / 2;
          }

          // Set delta before dialog is created.
          Drupal.paragraphsAddModal.setDelta($add_more_wrapper, delta);
          Drupal.paragraphsAddModal.openDialog($add_more_wrapper, $button.val());

          // Stop default execution of click event.
          event.preventDefault();
          event.stopPropagation();
        });
    }
  };

  /**
   * Namespace for modal related javascript methods.
   *
   * @type {Object}
   */
  Drupal.paragraphsAddModal = {};

  /**
   * Open modal dialog for adding new paragraph in list.
   *
   * @param {Object} $context
   *   jQuery element of form wrapper used to submit request for adding new
   *   paragraph to list. Wrapper also contains dialog template.
   * @param {string} title
   *   The title of the modal form window.
   */
  Drupal.paragraphsAddModal.openDialog = function ($context, title) {

    $context.dialog({
      modal: true,
      resizable: false,
      title: title,
      width: 'auto',
      close: function () {
        var $dialog = $(this);

        // Destroy dialog object.
        $dialog.dialog('destroy');
        // Reset delta after destroying the dialog object.
        var $delta = $dialog.closest('.clearfix')
          .find('.paragraph-type-add-modal-delta');
        $delta.val('');
      }
    });

    // Close the dialog after a button was clicked.
    $('.field-add-more-submit', $context)
      .each(function () {
      // Use mousedown event, because we are using ajax in the modal add mode
      // which explicitly suppresses the click event.
      $(this).on('mousedown', function () {
        var $this = $(this);
        $this.closest('div.ui-dialog-content').dialog('close');
      });
    });
  };

  Drupal.paragraphsAddModal.setDelta = function($add_more_wrapper, delta) {
    var $delta = $add_more_wrapper.closest('.clearfix').find('.paragraph-type-add-modal-delta');

    $delta.val(delta);
  };

  /**
   * Namespace for in between button handling methods.
   *
   * @type {Object}
   */
  Drupal.paragraphsAddModal.addInBetween = {};

  /**
   * Add single in between button row.
   *
   * @param index
   * @param rowElement
   */
  Drupal.paragraphsAddModal.addInBetween.addButton = function (index, rowElement) {
    // Create row with add in between button.
    var str = '' +
      '<tr class="add-in-between-row">' +
      '  <td colspan="100%">' +
      '    <div class="paragraph-type-add-modal">' +
      '      <input class="paragraph-type-add-modal-button button--small js-show button js-form-submit form-submit" type="submit" value="+ Add">' +
      '    </div>' +
      '  </td>' +
      '</tr>';
    var $buttonRow = $.parseHTML(str);

    $($buttonRow).insertBefore(rowElement);
  };


  /**
   * Init in between buttons for paragraphs table.
   *
   * @type {Object}
   */
  Drupal.behaviors.paragraphsInitAddInBetween = {
    attach: function () {
      var $tables = $('.paragraphs-tabs-wrapper .field-multiple-table')
        .once('init-in-between-buttons');

      $tables.each(function (index, table) {
        var $table = $(table);

        // Ensure that paragraph list uses modal dialog.
        if ($table.siblings('.clearfix').find('.paragraph-type-add-modal-button').length === 0) {
          return;
        }

        // Add buttons and adjust drag-drop functionality.
        $table.find('> tbody > tr')
          .each(Drupal.paragraphsAddModal.addInBetween.addButton);

        // Trigger attaching of behaviours for added buttons.
        Drupal.behaviors.paragraphsModalAdd.attach($table);
      });
    }
  };

  /**
   * Adjust drag-drop functionality for paragraphs with "add in between"
   * buttons.
   *
   * @param tableId
   */
  Drupal.paragraphsAddModal.addInBetween.adjustDragDrop = function (tableId) {
    // Ensure that function changes are executed only once.
    if (!Drupal.tableDrag[tableId] || Drupal.tableDrag[tableId].paragraphsDragDrop) {
      return;
    }
    Drupal.tableDrag[tableId].paragraphsDragDrop = true;

    // Helper function to create sequence execution of two bool functions.
    var sequenceBoolFunctions = function (originalFn, newFn) {
      return function () {
        var result = originalFn.apply(this, arguments);

        if (result) {
          result = newFn.apply(this, arguments);
        }

        return result;
      };
    };

    // Allow row swap if it's not in between button.
    var paragraphsIsValidSwap = function (row) {
      return !$(row).hasClass('add-in-between-row');
    };

    // Sequence default .isValidSwap() function with custom paragraphs function.
    var rowObject = Drupal.tableDrag[tableId].row;
    rowObject.prototype.isValidSwap = sequenceBoolFunctions(rowObject.prototype.isValidSwap, paragraphsIsValidSwap);

    // provide custom .onSwap() handler to reorder "Add" buttons.
    rowObject.prototype.onSwap = function (row) {
      var $table = $(row).closest('table');
      var allDrags = $table.find('tbody > tr.draggable');
      var allAdds = $table.find('tbody > tr.add-in-between-row');

      allDrags.each(function (index, dragElem) {
        var $paragraphRow = $(dragElem);
        if ($paragraphRow.prev('tr').hasClass('draggable')) {
          Drupal.detachBehaviors(allAdds[index], drupalSettings, 'move');
          $(dragElem).before(allAdds[index]);
          Drupal.attachBehaviors(allAdds[index], drupalSettings);
        }
      });
    };
  };

  /**
   * Init in between buttons for paragraphs table.
   *
   * @type {Object}
   */
  Drupal.behaviors.paragraphsAddInBetweenTableDragDrop = {
    attach: function () {
      for (var tableId in drupalSettings.tableDrag) {
        if (drupalSettings.tableDrag.hasOwnProperty(tableId)) {
          Drupal.paragraphsAddModal.addInBetween.adjustDragDrop(tableId);

          jQuery('#' + tableId)
            .once('in-between-buttons-columnschange')
            .on('columnschange', function () {
              var tableId = $(this).prop('id');

              Drupal.paragraphsAddModal.addInBetween.adjustDragDrop(tableId);
            });
        }
      }
    }
  };

}(jQuery, Drupal, drupalSettings));
