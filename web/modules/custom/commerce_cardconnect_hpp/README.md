# Commerce CardConnect CardPointe HPP

This module integrates [Drupal Commerce][] and the CardConnect [CardPointe Hosted Payment Page][] (HPP).

## Requirements

- [Drupal Commerce][]
- CardPointe HPP `MerchantId` and pay link.

## Usage

- Enable the _Commerce CardConnect CardPointe Hosted Payment Page_ module.
- Visit _Administration > Commerce > Configuration > Payment gateways_ and click _Add payment gateway_.
- In addition to required fields like _Name_ and _Display name_, be sure to select _CardPointe HPP (Hosted Payment Page)_ as the _Plugin_ and enter a _Merchant ID_ and a _Pay link_. These values are obtained from CardConnect.
- Note the `machine_name` of the new payment gateway. You'll need it later to update the CardPointe HPP webhook settings.
- Configure the rest of the payment gateway and save it.
- Configure your CardPointe HPP _Webhook URL_ and _Return to Website URL_ settings at https://SUBDOMAIN.securepayments.cardpointe.com/admin/hpp, where SUBDOMAIN is a unique identifier for your hosted payment page.

## Webhook URL

This module provides webhook support for completed offsite payments. The webhook ensures that payments are automatically added to orders for approved credit card transactions.

Commerce Payment automatically provides a webhook URL endpoint at `/payment/notify/PAYMENT_GATEWAY_MACHINE_NAME`. To update this setting in your CardPointe HPP, visit _Connect > Notifications_ and enter the full URL in the _Webhook URL_ field (e.g. https://example.com/payment/notify/PAYMENT_GATEWAY_MACHINE_NAME). Be sure to replace `PAYMENT_GATEWAY_MACHINE_NAME` with the machine name of the payment gateway you added above.

## Return to Website URL

This module provides a return URL for use in your CardPointe HPP configuration. This URL will redirect users to their completed order upon completion of their offsite payment.

The return URL path is `/commerce-cardconnect/cardpointe-hpp/payment-return`. To update this setting in your CardPointe HPP, visit _Connect > Notifications_ and enter the full URL in the _Return to Website URL_ field (e.g. https://example.com/commerce-cardconnect/cardpointe-hpp/payment-return). Be sure to click the _Save notification settings_ button.


[Drupal Commerce]: https://docs.drupalcommerce.org/commerce2
[CardPointe Hosted Payment Page]: https://support.cardconnect.com/cardpointe/hpp
