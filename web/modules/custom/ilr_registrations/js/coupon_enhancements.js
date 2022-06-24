/**
 * Enhancements for the coupon/discount code checkout form.
 */
(function ($, document) {

Drupal.behaviors.ilr_registrations_govt_nonprofit_helper = {

  attach: function (context, settings) {
    if (context != document) {
      return;
    }

    var $codeInput = $('input[data-drupal-selector="edit-coupon-redemption-form-code"], input[data-drupal-selector="edit-ilr-outreach-discount-redemption-form-code"]', context);

    if ($codeInput.length) {
      // Add a simple checkbox sibling element.
      var $helperCheckbox = $('<div class="ilr-govt-nonprofit"><input type="checkbox" id="ilr-govt-nonprofit" /> <label for="ilr-govt-nonprofit">' + Drupal.t('I am a government or non-profit employee') + '</label></div>');
      $codeInput.parent().parent().prepend($helperCheckbox);

      $helperCheckbox.on('click', function(ev) {
        $codeInput.val('GOV\'T/NONPROFIT').trigger('focus');
        $('input[data-drupal-selector="edit-coupon-redemption-form-apply"], input[data-drupal-selector="edit-ilr-outreach-discount-redemption-form-apply"]').trigger('mousedown');
      });
    }
  }
}

}(jQuery, document));
