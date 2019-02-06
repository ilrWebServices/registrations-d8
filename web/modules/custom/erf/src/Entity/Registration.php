<?php

namespace Drupal\erf\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the Registration entity.
 *
 * @ingroup erf
 *
 * @ContentEntityType(
 *   id = "registration",
 *   label = @Translation("Registration"),
 *   label_collection = @Translation("Registrations"),
 *   bundle_label = @Translation("Registration type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\erf\RegistrationListBuilder",
 *     "views_data" = "Drupal\erf\Entity\RegistrationViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\erf\Form\RegistrationForm",
 *       "add" = "Drupal\erf\Form\RegistrationForm",
 *       "edit" = "Drupal\erf\Form\RegistrationForm",
 *       "delete" = "Drupal\erf\Form\RegistrationDeleteForm",
 *     },
 *     "access" = "Drupal\erf\RegistrationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\erf\RegistrationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "registration",
 *   admin_permission = "administer registrations",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/registration/{registration}",
 *     "add-page" = "/registration/add",
 *     "add-form" = "/registration/add/{registration_type}",
 *     "edit-form" = "/registration/{registration}/edit",
 *     "delete-form" = "/registration/{registration}/delete",
 *     "collection" = "/admin/registrations/registrations",
 *   },
 *   bundle_entity_type = "registration_type",
 *   field_ui_base_route = "entity.registration_type.edit_form"
 * )
 */
class Registration extends ContentEntityBase implements RegistrationInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $registration_type = RegistrationType::load($this->bundle());
    $label = $this->t('@type registration #@id', [
      '@type' => $registration_type->label(),
      '@id' => $this->id(),
    ]);
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity() {
    if ($this->entity_type->value && $this->entity_id->value) {
      $entity_type = $this->entity_type->value;
      $entity_id = $this->entity_id->value;
      $source_entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);
      return $source_entity;
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Registration entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Associated Entity type'))
      ->setDescription(t('The entity type referenced by this registration submission.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    // @see https://cgit.drupalcode.org/webform/tree/src/Entity/WebformSubmission.php#n189
    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Associated Entity ID'))
      ->setDescription(t('The entity ID referenced by this registration submission.'))
      ->setSetting('max_length', 255);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
