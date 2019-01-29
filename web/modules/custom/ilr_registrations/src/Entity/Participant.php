<?php

namespace Drupal\ilr_registrations\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the Participant entity.
 *
 * @ingroup ilr_registrations
 *
 * @ContentEntityType(
 *   id = "participant",
 *   label = @Translation("Participant"),
 *   label_collection = @Translation("Participants"),
 *   bundle_label = @Translation("Participant type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ilr_registrations\ParticipantListBuilder",
 *     "views_data" = "Drupal\ilr_registrations\Entity\ParticipantViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\ilr_registrations\Form\ParticipantForm",
 *       "add" = "Drupal\ilr_registrations\Form\ParticipantForm",
 *       "edit" = "Drupal\ilr_registrations\Form\ParticipantForm",
 *       "delete" = "Drupal\ilr_registrations\Form\ParticipantDeleteForm",
 *     },
 *     "access" = "Drupal\ilr_registrations\ParticipantAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\ilr_registrations\ParticipantHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "participant",
 *   admin_permission = "administer participants",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uid" = "uid",
 *     "mail" = "mail",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/participant/{participant}",
 *     "add-page" = "/participant/add",
 *     "add-form" = "/participant/add/{participant_type}",
 *     "edit-form" = "/participant/{participant}/edit",
 *     "delete-form" = "/participant/{participant}/delete",
 *     "collection" = "/admin/registrations/participants",
 *   },
 *   bundle_entity_type = "participant_type",
 *   field_ui_base_route = "entity.participant_type.edit_form"
 * )
 */
class Participant extends ContentEntityBase implements ParticipantInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'creator_uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if ($this->mail->isEmpty()) {
      $participant_type = ParticipantType::load($this->bundle());
      $label = $this->t('@type participant #@id', [
        '@type' => $participant_type->label(),
        '@id' => $this->id(),
      ]);
    }
    else {
      $label = $this->getMail();
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getMail() {
    return $this->get('mail')->value;
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
  public function getOwner() {
    return $this->get('creator_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('creator_uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('creator_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('creator_uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Account'))
      ->setDescription(t('The user ID of the account associated with this Participant.'))
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
        'weight' => -9,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email of this participant.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'email_mailto',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => -10,
        'settings' => [
          'size' => '100',
          'placeholder' => 'email address',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['creator_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user ID of the creator/owner of this Participant.'))
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
        'weight' => 10,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
