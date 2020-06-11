<?php

namespace Drupal\ilr_registrations\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\salesforce\Rest\RestClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\salesforce_mapping\Entity\SalesforceMapping;
use Drupal\salesforce\SObject;
use Drupal\Core\Entity\EntityInterface;

/**
 * A Queue worker that processes data from the Salesforce Commerce webhook.
 *
 * @QueueWorker(
 *   id = "salesforce_commerce_webhook_processor",
 *   title = @Translation("Salesforce Commerce webhook processor"),
 *   cron = {"time" = 10}
 * )
 */
class SalesforceCommerceWebhookProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  /**
   * @var \Drupal\salesforce\Rest\RestClientInterface
   */
  protected $sfapi;

  /**
   * Constructs a new SalesforceCommerceWebhookProcessor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @param \Drupal\salesforce\Rest\RestClientInterface $sfapi
   *   The salesforce rest client.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RestClientInterface $sfapi) {
    $this->entityTypeManager = $entity_type_manager;
    $this->sfapi = $sfapi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('salesforce.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Verify that the salesforce order matches this commerce order by loading
    // the order object from salesforce.
    // TODO Verify that this fails if the SFID is incorrect.
    $order_mapping = $this->entityTypeManager->getStorage('salesforce_mapping')->load('ft_reg_order');
    $sf_order_object = $this->sfapi->objectRead('EXECED_Financial_Transaction__c', $data['sf_order_id']);
    $order = $this->entityTypeManager->getStorage('commerce_order')->load($data['pos_order_id']);
    $this->createMapping($order_mapping, $sf_order_object, $order, TRUE);

    // TODO Map the salesforce payment(s) to Commerce payment(s).

    // Map the salesforce customer contact to the Drupal user for the order billing profile user (not the billing profile entity itself).
    $contact_mapping = $this->entityTypeManager->getStorage('salesforce_mapping')->load('contact_user');
    $sf_customer_contact_object = $this->sfapi->objectRead('Contact', $data['customer']['sf_customer_contact_id']);
    $customer_user = $this->entityTypeManager->getStorage('user')->load($data['customer']['pos_customer_id']);
    $this->createMapping($contact_mapping, $sf_customer_contact_object, $customer_user);

    // Map the salesforce participants to the Drupal participant entities
    // and, if set, their associated user entities.
    $participant_mapping = $this->entityTypeManager->getStorage('salesforce_mapping')->load('basic_participant');

    foreach ($data['participants'] as $participant_data) {
      $sf_participant_object = $this->sfapi->objectRead('EXECED_Participant__c', $participant_data['sf_participant_id']);
      $participant = $this->entityTypeManager->getStorage('participant')->load($participant_data['pos_participant_id']);
      $this->createMapping($participant_mapping, $sf_participant_object, $participant);

      // If this participant was linked to a user, map that user to the salesforce contact.
      if (!$participant->uid->isEmpty()) {
        $sf_participant_contact_object = $this->sfapi->objectRead('Contact', $participant_data['sf_participant_contact_id']);
        $this->createMapping($contact_mapping, $sf_participant_contact_object, $participant->uid->entity);
      }
    }
  }

  /**
   * Create a salesforce mapping for a given entity.
   *
   * @param \Drupal\salesforce_mapping\Entity\SalesforceMapping $mapping
   *   A salesforce mapping config entity.
   *
   * @param \Drupal\salesforce\SObject $sf_object
   *   A salesforce object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity. E.g., commerce_order, user, or participant.
   *
   * @param bool $error_on_dupe
   *   When TRUE, an error will be thrown that will mark this queue item as
   *   failed if a mapping for this salesforce id already exists.
   *
   * @return null
   */
  protected function createMapping(SalesforceMapping $mapping, SObject $sf_object, EntityInterface $entity, $error_on_dupe = FALSE) {
    $mapped_object_storage = $this->entityTypeManager->getStorage('salesforce_mapped_object');

    // Map the salesforce order to the Commerce order.
    $mapped_object = $mapped_object_storage->loadByProperties([
      'salesforce_id' => (string) $sf_object->id(),
      'salesforce_mapping' => $mapping->id(),
    ]);

    if (empty($mapped_object)) {
      // Create the mapping.
      $mapped_object = $mapped_object_storage->create([
        'drupal_entity' => [
          'target_type' => $mapping->getDrupalEntityType(),
        ],
        'salesforce_mapping' => $mapping->id(),
        'salesforce_id' => (string) $sf_object->id(),
      ]);

      $mapped_object
        ->setDrupalEntity($entity)
        ->setSalesforceRecord($sf_object);

      // This updates any mapped entity fields with values from salesforce
      // (e.g., the Financial Transaction ID field on orders) and saves the
      // mapped object.
      $mapped_object->pull();
    }
    elseif ($error_on_dupe) {
      throw new \Exception('Duplicate Salesforce mapping: ' . $mapping->label());
    }
  }

}
