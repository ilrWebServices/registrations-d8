<?php

namespace Drupal\ilr_registrations\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\commerce_checkout\Attribute\CommerceCheckoutPane;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;

/**
 * Provides the order details pane.
 *
 * Displays the completed commerce_order, as would be viewed at
 *   /user/{user}/orders/{commerce_order}.
 */
#[CommerceCheckoutPane(
  id: "order_details",
  label: new TranslatableMarkup("Order details"),
  default_step: "complete",
)]
class OrderDetails extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $view_builder = $this->entityTypeManager->getViewBuilder('commerce_order');

    $pane_form['order_detail'] = [
      '#type' => 'container',
    ];

    $pane_form['order_detail']['heading'] = [
      '#type' => 'inline_template',
      '#template' => '<h2 class="cu-heading">{% trans %}Order number {{ number }}{% endtrans %}</h2>',
      '#context' => [
        'number' => $this->order->getOrderNumber(),
      ],
    ];

    $pane_form['order_detail']['info'] = $view_builder->view($this->order, 'user');

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {}

}
