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

## Notification URL

To ensure that payments are properly added to orders, you must update your CardPointe HPP settings to configure a _Webhook URL_. 

Since the CardPointe HPP is hosted on a separate site from your Drupal Commerce site, approved payment notifications are posted back to Drupal via a webhook when transactions are completed.

Commerce Payment automatically provides notification URL endpoints at `/payment/notify/PAYMENT_GATEWAY_MACHINE_NAME`.

Use the full URL of this endpoint (e.g. https://example.com/payment/notify/PAYMENT_GATEWAY_MACHINE_NAME) to configure the _Webhook URL_ in your CardPointe HPP. Be sure to replace `PAYMENT_GATEWAY_MACHINE_NAME` with the machine name of the payment gateway you added above.


[Drupal Commerce]: https://docs.drupalcommerce.org/commerce2
[CardPointe Hosted Payment Page]: https://support.cardconnect.com/cardpointe/hpp
