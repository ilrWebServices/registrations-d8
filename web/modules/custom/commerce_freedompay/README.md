# Commerce FreedomPay

This module integrates [Drupal Commerce][] and the [FreedomPay][] Hosted Payment Page (HPP).

## Requirements

- [Drupal Commerce][]
- Credentials for a FreedomPay HPP store and terminal. Freedompay documentation is blocked behind an authenticated wiki, but if you're a customer, you should have access to the customer and developer portals.

## Usage

- Enable the 'Freedompay Commerce' module.
- Visit _Administration > Commerce > Configuration > Payment gateways_ and click _Add payment gateway_.
- In addition to required fields like _Name_ and _Mode_, be sure to select _FreedomPay HPP (Hosted Payment Page)_ as the _Plugin_ and enter a _Store ID_ and a _Terminal ID_. These values are obtained from FreedomPay.
- Configure the rest of the payment gateway and save it.

## Return URLs

Since the FreedomPay HPP is hosted on a separate site from your Drupal Commerce site, users are returned to specific URLs on your commerce site when transactions are completed, failed, or canceled.

This module provides the following URL endpoints:

- `/commerce-freedompay/return`
- `/commerce-freedompay/success`
- `/commerce-freedompay/fail`
- `/commerce-freedompay/cancel`

You'll use the full URL of one or more of these endpoints (e.g. https://example.com/commerce-freedompay/return) when configuring your account with FreedomPay.

## Development

This module was developed while referencing the _FreedomPay HPP Service Developers Guide v2.11_, which can be downloaded from the FreedomPay developer portal.


[Drupal Commerce]: https://docs.drupalcommerce.org/commerce2
[FreedomPay]: https://corporate.freedompay.com/advanced-commerce-platform/security/
