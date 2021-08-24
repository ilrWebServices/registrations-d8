<?php

namespace Drupal\commerce_freedompay\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Controller for Freedompay payment transaction callbacks.
 *
 * Note that all of the Request objects for the methods in this class _should_
 * have a validated `transid` query parameter because of the access checks
 * made in `HPPResponseTransidAccess`.
 *
 * @see HPPResponseTransidAccess::access()
 * @see commerce_freedompay.routing.yml
 * @see commerce_freedompay.services.yml
 */
class HPPCallbackController extends ControllerBase {

  /**
   * Callback for the freedompay paths.
   *
   * - /commerce-freedompay/return
   * - /commerce-freedompay/success
   * - /commerce-freedompay/fail
   */
  public function return(Request $request) {
    $response = $this->getReturnResponse($request, 'commerce_payment.checkout.return');
    return $response;
  }

  /**
   * Callback for /commerce-freedompay/cancel .
   */
  public function cancel(Request $request) {
    $response = $this->getReturnResponse($request, 'commerce_payment.checkout.cancel');
    return $response;
  }

  /**
   * Creates a redirect URL for use with the offsite payment gateway, with a
   * `transid` query parameter included.
   */
  private function getReturnResponse(Request $request, $response_route) {
    $payment = $this->entityTypeManager()->getStorage('commerce_payment')->loadByProperties([
      'remote_id' => $request->query->get('transid'),
      'state' => 'new',
    ]);
    $payment = reset($payment);

    $commerce_payment_checkout_url = Url::fromRoute($response_route, [
      'commerce_order' => $payment->getOrder()->id(),
      'step' => 'payment',
    ], [
      'query' => [
        'transid' => $request->query->get('transid'),
      ],
      'absolute' => TRUE,
    ]);

    // Ensure that bubbleable metadata is collected and added to the response
    // object. @see https://drupal.stackexchange.com/a/187094
    $url = $commerce_payment_checkout_url->toString(TRUE);
    $response = new TrustedRedirectResponse($url->getGeneratedUrl());
    $response->addCacheableDependency($url);
    return $response;
  }

}
