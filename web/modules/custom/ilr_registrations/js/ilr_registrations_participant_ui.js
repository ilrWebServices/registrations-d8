/**
 * Enhancements for the IEF widget for erf participants.
 */
(function ($, window, document) {
  window.addEventListener('DOMContentLoaded', function () {
    var iefButtons = document.getElementById('edit-participants-wrapper').querySelectorAll('.cu-input--submit');

    if (iefButtons.length === 0) {
      return;
    }

    function updateIEFButtons() {
      iefButtons = document.getElementById('edit-participants-wrapper').querySelectorAll('.cu-input--submit');

      for (i = 0; i < iefButtons.length; i++) {
        var btnId = iefButtons[i].getAttribute('id');
        var btnValue = iefButtons[i].getAttribute('value');
        var shortValue; // The "icon"

        if (btnId.indexOf('ief-add') !== -1) {
          shortValue = '+';
        }

        if (btnId.indexOf('ief-edit-save') !== -1 || btnId.indexOf('ief-add-save') !== -1 || btnId.indexOf('ief-remove-confirm') !== -1) {
          shortValue = '✓';
        }

        if (btnId.indexOf('ief-entity-edit') !== -1) {
          shortValue = '✎';
        }

        if (btnId.indexOf('ief-entity-remove') !== -1) {
          shortValue = '×';
        }

        if (btnId.indexOf('ief-add-cancel') !== -1 || btnId.indexOf('ief-edit-cancel') !== -1 || btnId.indexOf('ief-remove-cancel') !== -1) {
          shortValue = "×";
        }

        iefButtons[i].setAttribute('aria-label', btnValue);
        iefButtons[i].setAttribute('title', btnValue); // For hover
        iefButtons[i].setAttribute('value', shortValue);
      }
    }

    updateIEFButtons();

    $(document).ajaxComplete(function (e, xhr, settings) {
      if (settings.extraData && settings.extraData._triggering_element_name.startsWith('ief')) {
        updateIEFButtons();
      }
    });

  });
}(jQuery, window, document));
