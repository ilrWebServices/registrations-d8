<?php

namespace Drupal\ilr_registrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for IlrRegistrationsController.
 */
class IlrRegistrationsController extends ControllerBase {

  /**
   * Callback for /class/{salesforce_class_id}.
   */
  public function sfIdRedirect($salesforce_id, Request $request) {
    $mapped_object_storage = $this->entityTypeManager()->getStorage('salesforce_mapped_object');

    // Find a salesforce_mapped_object with $salesforce_id.
    $mapped_object = $mapped_object_storage->loadByProperties([
      'salesforce_id' => $salesforce_id,
    ]);

    if (empty($mapped_object)) {
      throw new NotFoundHttpException();
    }

    $mapped_object = reset($mapped_object);
    $mapped_entity = $mapped_object->getMappedEntity();

    // Ensure that the Drupal entity is a `commerce_product_variation` of the
    // type `class`.
    if (!$mapped_entity instanceof ProductVariationInterface || $mapped_entity->bundle() !== 'class') {
      throw new NotFoundHttpException();
    }

    // Redirect to /product/{product_id}?v={variation_id}.
    $product_url = Url::fromRoute('entity.commerce_product.canonical', [
      'commerce_product' => $mapped_entity->product_id->target_id,
    ], [
      'query' => ['v' => $mapped_entity->id()],
      'absolute' => TRUE,
    ]);

    // Ensure that bubbleable metadata is collected and added to the response
    // object. @see https://drupal.stackexchange.com/a/187094
    $url = $product_url->toString(TRUE);
    $response = new TrustedRedirectResponse($url->getGeneratedUrl());
    $response->addCacheableDependency($url);
    return $response;
  }

}
