/**
 * @file commerce_order_item_render.widget.js
 */

(function ($, Drupal) {

  Drupal.behaviors.CommerceOrderItemRenderWidgetSelect = {
    attach: function attach(context) {
      $("*[data-variation-render]").once().on('click', function(e) {
        e.preventDefault();
        $(e.currentTarget).find("input[data-variation-render-input]")
          .prop('checked', true)
          .trigger('change');
      });
    }
  };

})(jQuery, Drupal);
