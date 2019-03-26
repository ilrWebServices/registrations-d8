<?php

namespace Drupal\commerce_freedompay\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Entity\PaymentInterface;

class TransactionController extends ControllerBase {

  /**
   * Callback for /admin/commerce-freedompay/transaction/{commerce_payment} .
   */
  public function viewTransaction(PaymentInterface $commerce_payment, Request $request) {
    $payment_gateway = $commerce_payment->getPaymentGateway();
    $transid = $commerce_payment->getRemoteId();
    // @todo Check to see if there's a value for the transaction id.
    $transaction = $payment_gateway->getPlugin()->getTransaction($transid);

    return [
      '#markup' => '<pre>' . print_r($transaction, TRUE) . '</pre>'
    ];
  }

}
