<?php

namespace Drupal\ilr_registrations\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom message pane.
 *
 * @CommerceCheckoutPane(
 *   id = "participant_info",
 *   label = @Translation("Participant info"),
 * )
 */
class ParticipantInfo extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $registration_storage = $this->entityTypeManager->getStorage('registration');
    $customer = $this->order->getCustomer();
    $billing_profile = $this->order->getBillingProfile();
    $billing_profile_fieldnames = array_keys($billing_profile->getFields());
    // dump($billing_profile);
    // dump($billing_profile->getFields());

    foreach ($this->order->getItems() as $order_item) {
      $registrations = $registration_storage->loadByProperties([
        'commerce_order_item_id' => $order_item->id(),
      ]);

      if (!empty($registrations)) {
        $registration = reset($registrations);
        $form_group = 'participant_for_' . $order_item->id();

        $pane_form[$form_group] = [
          // '#type' => 'fieldset',
          '#type' => 'container',
          '#title' => $this->t('Participants for ' . $order_item->label()),
        ];

        foreach ($registration->participants->referencedEntities() as $delta => $participant) {

          // Pre-fill the participant values from the billing profile if the
          // customer email matches the participant email.
          if ($participant->mail->value === $customer->mail->value) {
            $participant_fieldnames = array_keys($participant->getFields());

            foreach ($billing_profile_fieldnames as $profile_field_name) {
              if ($profile_field_name === 'address') {
                $participant_field_name = 'field_address';
                dump($billing_profile->get('address'));
              }
              elseif (strpos($profile_field_name, 'field_') === 0 && in_array($profile_field_name, $participant_fieldnames)) {
                // dump($profile_field_name);
                $participant->set($profile_field_name, $billing_profile->get($profile_field_name)->value);
              }
              else {
                continue;
              }

              // dump($profile_field_name);
              // dump($participant_field_name);
            }
          }

          // $pane_form[$form_group]['participant_' . $participant->id()] = [
          $pane_form[$form_group][$delta] = [
            '#type' => 'fieldset',
            '#title' => $participant->label(),
          ];

          $pane_form[$form_group][$delta]['item'] = [
            '#type' => 'inline_entity_form',
            '#entity_type' => 'participant',
            '#bundle' => $participant->bundle(),
            '#default_value' => $participant,
            '#form_mode' => 'inline',
            '#save_entity' => TRUE,
            '#op' => 'edit',
            '#ief_id' => 'participant_subform-' . $participant->id(),
            // '#access' => $participant->access('update', $this->account),
          ];
        }
      }
    }

    // Set the bare minimum 'inline_entity_form' value to the form state. This
    // is required to add the proper submit handlers to the  form. See
    // inline_entity_form_form_alter() in inline_entity_form.module
    $form_state->set(['inline_entity_form'], []);

    return $pane_form;
  }

  public function isVisible() {
    $registration_storage = $this->entityTypeManager->getStorage('registration');

    foreach ($this->order->getItems() as $order_item) {
      $registrations = $registration_storage->loadByProperties([
        'commerce_order_item_id' => $order_item->id(),
      ]);

      if (!empty($registrations)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
