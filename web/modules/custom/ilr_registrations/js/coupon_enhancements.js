/**
 * Enhancements for the coupon/discount code checkout form.
 */
(function ($, document) {

let code_to_apply = '';

Drupal.behaviors.ilr_registrations_govt_nonprofit_helper = {

  attach: function (context, settings) {
    let $codeFieldSet = $('#edit-ilr-outreach-discount-redemption-form', context);
    let $codeInput = $('input[data-drupal-selector="edit-ilr-outreach-discount-redemption-form-code"]', context);
    let $appliedCode = $('.ilr-outreach-discount-applied-item', context).text().trim();
    let $showHelperCheckbox = (($codeInput.length || $appliedCode === 'EBIRD') && $('#ilr-govt-nonprofit').length === 0);

    if (code_to_apply && $codeInput.length) {
      $codeInput.val(code_to_apply).trigger('focus');

      // Wait for the Drupal ajax event to be added to this element.
      setTimeout(function() {
        $('input[data-drupal-selector="edit-ilr-outreach-discount-redemption-form-apply"]').trigger('mousedown');
        code_to_apply = '';
      }, 250);

      return;
    }

    if ($showHelperCheckbox) {
      var $helperCheckbox = $('<div class="ilr-govt-nonprofit"><input type="checkbox" id="ilr-govt-nonprofit" /> <label for="ilr-govt-nonprofit">' + Drupal.t('I am a government or non-profit employee') + '</label></div>');

      $codeFieldSet.find('legend').after($helperCheckbox);

      $helperCheckbox.on('click', 'input', function(ev) {
        if ($appliedCode === 'EBIRD') {
          // Click the remove discount button and set a new code to apply. The
          // mousedown will trigger an ajax submission of the form, so we can't
          // update text input for the discount code yet. The ajax call will
          // cause the attach function to run again, and it will notice early on
          // that there is a code to apply and do it then.
          $('input[data-drupal-selector="edit-ilr-outreach-discount-redemption-form-remove-discount-1"]').trigger('mousedown');
          code_to_apply = 'EBIRD-GOV\'T/NONPROFIT';
        }
        else {
          $codeInput.val('GOV\'T/NONPROFIT').trigger('focus');
          $('input[data-drupal-selector="edit-ilr-outreach-discount-redemption-form-apply"]').trigger('mousedown');
        }
      });
    }
  }
}

}(jQuery, document));
