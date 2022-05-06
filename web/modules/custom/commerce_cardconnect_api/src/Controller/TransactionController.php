<?php

namespace Drupal\commerce_cardconnect_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * CardPointe API transaction controller.
 */
class TransactionController extends ControllerBase {

  /**
   * Callback for /admin/commerce-freedompay/transaction/{commerce_payment} .
   */
  public function viewTransaction(PaymentInterface $commerce_payment, Request $request) {
    $payment_gateway = $commerce_payment->getPaymentGateway();
    $retref = $commerce_payment->getRemoteId();
    // @todo Check to see if there's a value for the retref.
    $transaction = $payment_gateway->getPlugin()->getTransaction($retref);

    $build = [
      '#theme' => 'table',
      '#header' => ['Key', 'Value'],
      '#rows' => [],
    ];

    foreach ($transaction as $key => $val) {
      $build['#rows'][] = [$key, $val];
    }

    return $build;

    return [
      '#markup' => '<pre>' . print_r($transaction, TRUE) . '</pre>',
    ];
  }

}
