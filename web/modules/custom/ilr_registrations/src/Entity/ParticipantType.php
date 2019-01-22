<?php

namespace Drupal\ilr_registrations\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Participant type entity.
 *
 * @ConfigEntityType(
 *   id = "participant_type",
 *   label = @Translation("Participant type"),
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
 *     "canonical" = "/admin/structure/participant_type/{participant_type}",
 *     "add-form" = "/admin/structure/participant_type/add",
 *     "edit-form" = "/admin/structure/participant_type/{participant_type}/edit",
 *     "delete-form" = "/admin/structure/participant_type/{participant_type}/delete",
 *     "collection" = "/admin/structure/participant_type"
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
