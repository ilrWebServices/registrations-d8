<?php

namespace Drupal\ilr_outreach_discounts\Plugin\Commerce\InlineForm;

use Drupal\commerce\Plugin\Commerce\InlineForm\InlineFormBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ilr_outreach_discounts\IlrOutreachDiscount;
use Drupal\salesforce\Rest\RestClient;
use Drupal\salesforce\SelectQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an inline form for redeeming an ILR outreach discount.
 *
 * @CommerceInlineForm(
 *   id = "ilr_outreach_discount_redemption",
 *   label = @Translation("ILR Outreach discount redemption"),
 * )
 */
class DiscountRedemption extends InlineFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Salesforce client.
   *
   * @var \Drupal\salesforce\Rest\RestClient
   */
  protected $client;

  /**
   * Constructs a new DiscountRedemption object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\salesforce\Rest\RestClient $client
   *   The Salesforce client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RestClient $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('salesforce.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // The order_id is passed via configuration to avoid serializing the
      // order, which is loaded from scratch in the submit handler to minimize
      // chances of a conflicting save.
      'order_id' => '',
      // NULL for unlimited.
      'max_coupons' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function requiredConfiguration() {
    return ['order_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {
    $inline_form = parent::buildInlineForm($inline_form, $form_state);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entityTypeManager->getStorage('commerce_order')->load($this->configuration['order_id']);

    if (!$order) {
      throw new \RuntimeException('Invalid order_id given to the coupon_redemption inline form.');
    }

    assert($order instanceof OrderInterface);
    $ilr_outreach_discounts = $order->getData('ilr_outreach_discounts', []);

    $inline_form = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => $this->t('Discount codes'),
      '#attached' => [
        'library' => ['ilr_registrations/coupon_enhancements'],
      ],
      '#access' => $order->getBalance()->isPositive() || !empty($ilr_outreach_discounts),
      '#configuration' => $this->getConfiguration(),
    ] + $inline_form;
    $inline_form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code'),
      // Chrome autofills this field with the address line 1, and ignores
      // autocomplete => 'off', but respects 'new-password'.
      '#attributes' => [
        'autocomplete' => 'new-password',
      ],
    ];
    $inline_form['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply discount'),
      '#name' => 'apply_discount',
      '#limit_validation_errors' => [
        $inline_form['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'applyDiscount'],
      ],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefreshForm'],
        'element' => $inline_form['#parents'],
      ],
    ];

    $max_coupons = $this->configuration['max_coupons'];

    if ($max_coupons && count($ilr_outreach_discounts) >= $max_coupons) {
      // Don't allow additional coupons to be added.
      $inline_form['code']['#access'] = FALSE;
      $inline_form['apply']['#access'] = FALSE;
    }

    $index = 0;

    // Display applied discounts.
    foreach ($ilr_outreach_discounts as $discount_code => $discount) {
      $index++;

      $inline_form['discounts'][$index]['code'] = [
        '#plain_text' => $discount_code,
      ];
      $inline_form['discounts'][$index]['remove_button'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove discount'),
        '#name' => 'remove_discount_' . $index,
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefreshForm'],
          'element' => $inline_form['#parents'],
        ],
        '#weight' => 50,
        '#limit_validation_errors' => [
          $inline_form['#parents'],
        ],
        '#discount_code' => $discount_code,
        '#submit' => [
          [get_called_class(), 'removeDiscount'],
        ],
        // Simplify ajaxRefresh() by having all triggering elements
        // on the same level.
        '#parents' => array_merge($inline_form['#parents'], ['remove_discount_' . $index]),
      ];
    }

    return $inline_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::validateInlineForm($inline_form, $form_state);

    // Runs if the 'Apply discount' button was clicked, or the main form was
    // submitted by the user clicking the primary submit button.
    $triggering_element = $form_state->getTriggeringElement();
    $button_type = isset($triggering_element['#button_type']) ? $triggering_element['#button_type'] : NULL;

    if ($triggering_element['#name'] != 'apply_discount' && $button_type != 'primary') {
      return;
    }

    $discount_code_parents = array_merge($inline_form['#parents'], ['code']);
    $discount_code_path = implode('][', $discount_code_parents);
    $discount_code = $form_state->getValue($discount_code_parents);

    if (empty($discount_code)) {
      if ($triggering_element['#name'] == 'apply_discount') {
        $form_state->setErrorByName($discount_code_path, $this->t('Please provide a discount code.'));
      }
      return;
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entityTypeManager->getStorage('commerce_order')->load($this->configuration['order_id']);

    $eligible_discounts = [];
    $messages = [];

    /** @var \Drupal\commerce_order\Entity\OrderItem $item */
    foreach ($order->getItems() as $item) {
      // @see ilr_registrations_commerce_order_item_presave() for 'sf_class_id'.
      $error = '';
      $discount_code_object = $this->getEligibleClassDiscount($item->getData('sf_class_id'), $discount_code, $error);

      if ($discount_code_object) {
        $eligible_discount = new IlrOutreachDiscount;
        $eligible_discount->code = $discount_code;
        $eligible_discount->sfid = $discount_code_object->id();
        $eligible_discount->universal = $discount_code_object->field('Universal__c');

        if ($discount_code_object->field('Discount_Type__c') === 'Individual_Percentage') {
          $eligible_discount->type = 'percentage';
          $eligible_discount->value =  $discount_code_object->field('Discount_Percent__c') / -100;
        }
        else {
          $eligible_discount->type = 'amount';
          $eligible_discount->value = $discount_code_object->field('Discount_Amount__c') * -1;
        }

        // Only store 'eligible' rules for this discount code. `appliesTo`
        // should end up as an array of Salesforce class object IDs for which
        // this non-universal code applies.
        if ($discount_classes = $discount_code_object->field('Discount_Classes__r')) {
          foreach ($discount_classes['records'] as $discount_class) {
            if ($discount_class['Eligible__c']) {
              $eligible_discount->appliesTo[] = $discount_class['Class__c'];
            }
            else {
              $eligible_discount->excludes[] = $discount_class['Class__c'];
            }
          }
        }

        $eligible_discounts[$discount_code] = $eligible_discount;
      }
      else {
        $messages[] = $error;
      }
    }

    if (empty($eligible_discounts)) {
      foreach ($messages as $message) {
        $form_state->setErrorByName($discount_code_path, $message);
      }
      return;
    }

    $form_state->set('ilr_outreach_discounts', $eligible_discounts);
  }

  /**
   * Submit callback for the "Apply discount" button.
   */
  public static function applyDiscount(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);

    // Clear the discount code input.
    $user_input = &$form_state->getUserInput();
    NestedArray::setValue($user_input, array_merge($parents, ['code']), '');

    // `ilr_outreach_discounts` is set in the form state in validateInlineForm().
    if ($discounts = $form_state->get('ilr_outreach_discounts')) {
      $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');

      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $order_storage->load($inline_form['#configuration']['order_id']);

      // Store applicable discounts in the order data. Discounts are actually
      // applied to the order in IlrOutreachDiscountOrderProcessor::process().
      $order->setData('ilr_outreach_discounts', $discounts);
      $order->save();
    }

    $form_state->setRebuild();
  }

  /**
   * Submit callback for the "Remove discount" button.
   */
  public static function removeDiscount(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($inline_form['#configuration']['order_id']);
    $ilr_outreach_discounts = $order->getData('ilr_outreach_discounts', []);

    if (!empty($ilr_outreach_discounts[$triggering_element['#discount_code']])) {
      unset($ilr_outreach_discounts[$triggering_element['#discount_code']]);
      $order->setData('ilr_outreach_discounts', $ilr_outreach_discounts);
      $order->save();
    }

    $form_state->setRebuild();
  }

  /**
   * Get eligible class discounts directly from Salesforce.
   *
   * From Dan E. on May 12th, 2022:
   * 1) Query the rule that matches the class and discount IDs.
   * 2) If it’s marked as “Eligible” and the registration date is between the
   *    discount start and end dates, it is eligible
   * 3) If there are NO Discount_Class rules that match and the discount is
   *    marked as “Universal” and the registration date is between the discount
   *    start and end dates, it’s eligible
   * 4) Otherwise, it’s not eligible
   *
   * @param string $class_sf_id
   *   The salesforce id of the class.
   * @param string $discount_code
   *   The discount code.
   *
   * @return \Drupal\salesforce\SObject|FALSE
   *   Discount info if class is eligible for this discount. FALSE if class is
   *   not eligible.
   */
  protected function getEligibleClassDiscount(string $class_sf_id, string $discount_code, string &$error = NULL) {
    // Get the discount code, along with any discount class 'rule' records.
    $soql_query = new SelectQuery('EXECED_Discount_Code__c');
    $soql_query->fields = [
      'Id',
      'Name',
      'Discount_Amount__c',
      'Discount_Percent__c',
      'Discount_Type__c',
      'Discount_Start_Date__c',
      'Discount_End_Date__c',
      'Universal__c',
      "(SELECT Id, Name, Class__c, Eligible__c FROM Discount_Classes__r)",
    ];
    $soql_query->addCondition('Name', "'" . addslashes($discount_code) . "'");
    $soql_query->addCondition('Discount_Type__c', ['Individual_Percentage', 'Individual_Amount']);
    $results = $this->client->query($soql_query);

    if ($results->size()) {
      $discount_records = $results->records();

      /** @var \Drupal\salesforce\SObject $discount_code_object */
      $discount_code_object = reset($discount_records);
    }
    else {
      $error = 'No such discount code.';
      return FALSE;
    }

    $discount_start_date = new \DateTime($discount_code_object->field('Discount_Start_Date__c'));
    $discount_end_date = new \DateTime($discount_code_object->field('Discount_End_Date__c'));
    $now_date = new \DateTime('now');
    $rules_for_class = [];

    // Gather the rules for only this class. We collect all of the rules,
    // including those for other classes, so the orderprocessor can see them.
    if (($rules = $discount_code_object->field('Discount_Classes__r')) && isset($rules['records'])) {
      foreach ($rules['records'] as $rule) {
        if ($rule['Class__c'] === $class_sf_id) {
          $rules_for_class[] = $rule;
        }
      }
    }

    // If there is a discount start date and it's in the future, not eligible.
    if ($discount_code_object->field('Discount_Start_Date__c') && $discount_start_date > $now_date) {
      $error = 'Discount currently ineligible.';
      return FALSE;
    }
    // If there is a discount end date and it's in the past, not eligible.
    elseif ($discount_code_object->field('Discount_End_Date__c') && $discount_end_date < $now_date) {
      $error = 'Discount no longer eligible.';
      return FALSE;
    }
    // If there are 'rules' for this discount/class combo.
    elseif ($rules_for_class) {
      foreach ($rules_for_class as $rule) {
        // If any rule for this class is not eligible, this discount is not eligible.
        if ($rule['Eligible__c'] === FALSE) {
          $error = 'Discount not eligible for class ' . $class_sf_id;
          return FALSE;
        }
      }
    }
    // There are no rules for this discount/class combo.
    elseif (!$discount_code_object->field('Universal__c')) {
      $error = 'Discount not applicable.';
      return FALSE;
    }

    return $discount_code_object;
  }

}
