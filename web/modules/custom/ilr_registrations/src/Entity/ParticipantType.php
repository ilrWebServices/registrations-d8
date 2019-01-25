<?php

namespace Drupal\ilr_registrations\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Participant type entity.
 *
 * @ConfigEntityType(
 *   id = "participant_type",
 *   label = @Translation("Participant type"),
 *   label_collection = @Translation("Participant types"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ilr_registrations\ParticipantTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ilr_registrations\Form\ParticipantTypeForm",
 *       "edit" = "Drupal\ilr_registrations\Form\ParticipantTypeForm",
 *       "delete" = "Drupal\ilr_registrations\Form\ParticipantTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ilr_registrations\ParticipantTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "participant_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "participant",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/registrations/participant_types/{participant_type}",
 *     "add-form" = "/admin/registrations/participant_types/add",
 *     "edit-form" = "/admin/registrations/participant_types/{participant_type}/edit",
 *     "delete-form" = "/admin/registrations/participant_types/{participant_type}/delete",
 *     "collection" = "/admin/registrations/participant_types"
 *   }
 * )
 */
class ParticipantType extends ConfigEntityBundleBase implements ParticipantTypeInterface {

  /**
   * The Participant type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Participant type label.
   *
   * @var string
   */
  protected $label;

}
