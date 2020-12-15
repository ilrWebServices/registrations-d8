<?php

namespace Drupal\commerce_cardconnect_hpp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Cardpointe HPP payment return.
 *
 * @see commerce_cardconnect_hpp.routing.yml
 */
class CardpointeHppController extends ControllerBase {

  /**
   * Callback for /commerce-cardconnect/cardpointe-hpp/payment-return .
   */
  public function return(Request $request) {
    if ($this->currentUser()->isAnonymous()) {
      $this->messenger()->addWarning('user is anonymous');
      return $this->redirect('user.page', [], [], 307);
    }

    $tempstore_shared = \Drupal::service('tempstore.shared')->get('cardpointe_hpp');
    $remote_payment_id = $tempstore_shared->get('cardpointe_hpp_transaction_id_for_user:' . $this->currentUser()->id());

    if (!$remote_payment_id) {
      $this->messenger()->addWarning('no recently created payment for this user');
      return $this->redirect('user.page', [], [], 307);
    }

    // Load the payment for this remote payment id.
    $payment = $this->entityTypeManager()->getStorage('commerce_payment')->loadByRemoteId($remote_payment_id);

    if (!$payment) {
      $this->messenger()->addWarning('payment not found');
      return $this->redirect('user.page', [], [], 307);
    }

    $order = $payment->getOrder();

    // Redirect to /checkout/ORDER_ID/payment/return.
    return $this->redirect('commerce_payment.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], [], 307);
  }

}
