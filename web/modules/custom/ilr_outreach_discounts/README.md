# ILR Outreach Discounts

This module provides Drupal Commerce support for classes and discounts from the ILR Outreach Salesforce instances.

Previously, discounts were synchronized from Salesforce custom `EXECED_Discount_Code__c` objects to Commerce Promotion entities.

This module replaces that with the following:

1. A custom `CommerceCheckoutPane` called `ilr_outreach_discount_redemption`.
2. A custom `ilr_outreach_discount` adjustment type.
3. The custom `IlrOutreachDiscountOrderProcessor` Commerce order processor.

Discount codes are applied by querying Salesforce in real time and applying eligibility rules as defined in `Drupal\ilr_outreach_discounts\Plugin\Commerce\InlineForm\DiscountRedemption::getEligibleDiscount()`. These rules are checked during the checkout pane inline form validation. In the future, these rules may be defined in Salesforce.

Here's a basic overview of the discount code flow:

1. User enters a discount code in an inline form during checkout.
2. The inline form validator calls a function that queries Salesforce for the discount code object and any `Discount_Class__c` rule objects.
3. If the discount code can apply to _any_ item in the order, a concise version of the discount is stored in the order data as an `IlrOutreachDiscount` object.
4. The `IlrOutreachDiscountOrderProcessor` runs and adds `ilr_outreach_discount` adjustments to the order _items_ as applicable. There can be cases in which a discount is only applicable to some of the items in the order.

Any time the order is modified, such as when classes or participants are added or removed, the `IlrOutreachDiscountOrderProcessor` is re-run, and any discount codes stored in the order data are re-evaluated.
