<?php

namespace Drupal\commerce_cardconnect_hpp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for Cardpointe HPP payment return.
 *
 * @see commerce_cardconnect_hpp.routing.yml
 */
class CardpointeHppController extends ControllerBase {

  /**
   * A shared tempstore for `cardpointe_hpp`.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempstore_shared;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->tempstore_shared = $container->get('tempstore.shared')->get('cardpointe_hpp');
    return $instance;
  }

  /**
   * Callback for /commerce-cardconnect/cardpointe-hpp/payment-return .
   */
  public function return(Request $request) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException('User was anonymous');
    }

    $remote_payment_id = $this->tempstore_shared->get('cardpointe_hpp_transaction_id_for_user:' . $this->currentUser()->id());

    if (!$remote_payment_id) {
      throw new AccessDeniedHttpException('Recently created payment for user ' . $this->currentUser()->id() . ' not found');
    }

    // Load the payment for this remote payment id.
    $payment = $this->entityTypeManager()->getStorage('commerce_payment')->loadByRemoteId($remote_payment_id);

    if (!$payment) {
      throw new AccessDeniedHttpException('Remote payment ' . $remote_payment_id . ' not found');
    }

    $order = $payment->getOrder();

    // Redirect to /checkout/ORDER_ID/payment/return.
    return $this->redirect('commerce_payment.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], [], 307);
  }

}
