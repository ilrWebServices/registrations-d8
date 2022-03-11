<?php

namespace Drupal\commerce_cardconnect_api\PluginForm;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class CardPointeApiPaymentMethodAddForm extends PaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_cardconnect_api\Plugin\Commerce\PaymentGateway\CardPointeApi $plugin */
    $plugin = $this->plugin;

    $tokenizer_hostname = sprintf('https://fts%s.cardconnect.com', $plugin->getMode() === 'live' ? '' : '-uat');

    $tokenizer_url = Url::fromUri($tokenizer_hostname . '/itoke/ajax-tokenizer.html', [
      'absolute' => TRUE,
      'query' => [
        'useexpiry' => 'true',
        'usecvv' => 'true',
        'invalidcreditcardevent' => 'true',
        'invalidcvvevent' => 'true',
        'invalidexpiryevent' => 'true',
        'autofocus' => 'true',
        'inactivityto' => '10',
        'css' => '
          body{margin:0;padding-left:2px;}
          .error{border-color:#b31b1b;}
          input{border-radius:3px;border-style:solid;border-style-width:1px;border-color:#ccc;padding:.5em;margin:.5em 0 1em; font-size: 1.2em;}
          label{font-family:sans-serif;font-weight:300;}
          select{margin-bottom:1em;font-size:1.2em;padding:.25em}',
      ],
    ]);

    // This uses `inline_template` because `markup` strips iframe.
    $element['tokenizer'] = [
      '#type' => 'inline_template',
      '#template' => <<<JSC
      <script language="JavaScript">
        window.addEventListener('message', function(e) {
          if (e.origin === '$tokenizer_hostname') {
            const data = JSON.parse(e.data);

            if (data.validationError) {
              document.querySelector('.cp-token').value = '';
              document.querySelector('.cp-expiry').value = '';
              document.querySelector('.cp-validation-error').value = data.validationError;
            }
            else {
              document.querySelector('.cp-token').value = data.message;
              document.querySelector('.cp-expiry').value = data.expiry;
              document.querySelector('.cp-validation-error').value = '';
            }
          }
        }, false);
      </script>
      <iframe id="tokenFrame" name="tokenFrame" src="{{ url }}" height="300px" width="100%" scrolling="no"></iframe>
      JSC,
      '#context' => [
        'url' => $tokenizer_url->toString(),
      ],
    ];

    $element['token'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['cp-token']
      ],
    ];

    $element['expiry'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['cp-expiry']
      ],
    ];

    $element['validation_error'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['cp-validation-error']
      ],
    ];

    // Revisit once we decide to implement reusable stored payment methods and
    // https://www.drupal.org/project/commerce/issues/2871483 lands.
    // $element['save_card'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Save card'),
    // ];

    $element['#weight'] = 20;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    if (!empty($values['validation_error'])) {
      $form_state->setError($element['tokenizer'], $values['validation_error']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    // CardPointe tokens use the second number (after the 9) to represent the
    // card type. See https://developer.cardpointe.com/guides/cardsecure
    $type = CreditCard::detectType(substr($values['token'], 1));

    $this->entity->card_type = $type->getId();
    $this->entity->card_number = substr($values['token'], -4);
    $this->entity->remote_id = $values['token'];
    $this->entity->card_exp_month = substr($values['expiry'], 4);
    $this->entity->card_exp_year = substr($values['expiry'], 0, 4);
    $this->entity->reusable = 0;
  }

}
