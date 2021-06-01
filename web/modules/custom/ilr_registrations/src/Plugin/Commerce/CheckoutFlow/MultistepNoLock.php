<?php

namespace Drupal\ilr_registrations\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;

/**
 * Provides a multistep checkout flow that does not lock the order on payment.
 *
 * @CommerceCheckoutFlow(
 *   id = "multistep_no_lock",
 *   label = "Multistep - No Lock",
 * )
 */
class MultistepNoLock extends CheckoutFlowWithPanesBase {

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    // Note that previous_label and next_label are not the labels
    // shown on the step itself. Instead, they are the labels shown
    // when going back to the step, or proceeding to the step.
    return [
      'login' => [
        'label' => $this->t('Login'),
        'previous_label' => $this->t('Go back'),
        'has_sidebar' => FALSE,
      ],
      'order_information' => [
        'label' => $this->t('Order information'),
        'has_sidebar' => TRUE,
        'previous_label' => $this->t('Go back'),
      ],
    ] + parent::getSteps();
  }

  /**
   * Reacts to the current step changing.
   *
   * Called before saving the order and redirecting. This is overridden from
   * CheckoutFlowBase to prevent the locking of the order on the payment step.
   * This is useful for processors like CardPointe that have no cancel callback
   * to unlock the order when there are payment issues.
   *
   * Handles the following logic
   * 1) Places the order before the complete page.
   *
   * @param string $step_id The new step ID.
   */
  protected function onStepChange($step_id) {
    // Place the order.
    if ($step_id == 'complete' && $this->order->getState()->getId() == 'draft') {
      // Notify other modules.
      $event = new OrderEvent($this->order);
      $this->eventDispatcher->dispatch(CheckoutEvents::COMPLETION, $event);
      $this->order->getState()->applyTransitionById('place');
    }
  }

}
