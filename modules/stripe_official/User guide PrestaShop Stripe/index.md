---
title: Stripe Connector for PrestaShop
route: /connectors/prestashop
redirects:
  - /plugins/prestashop

subtitle: Learn how to help your customers check out and accept payments using the PrestaShop connector.
stripe_products: []
---

Use the Stripe Connector for [PrestaShop](https://www.prestashop.com/en) to build an integration that allows you to accept payments in many countries. The connector integrates [Stripe Elements](/payments/elements), an embedded UI component that lets you accept more than 25 payment methods with a single integration and that comes with the following features:

{% list type="checkmark" %}
- **Built-in conversion logic**: Increase conversion by reducing user friction and errors with features such as address auto-complete, real-time card validation, descriptive error messages, and third-party auto-fill.
- **Global payment conversion**: Dynamically display the right language, currency, and payment methods most likely to improve conversion. Stripe supports over 25 languages, 135 currencies, and 25 payment methods.
- **Authorize payments and capture later**: Stripe supports separate card authorization and capture, which lets you collect card information, verify sufficient funds, and then capture the total amount after shipping.
- **Works with any device**: Provide customers with a responsive checkout across mobile, tablet, and desktop, and offer Apple Pay and Google Pay out of the box.
{% /list %}

{% image src="images/prestashop/prestashop-dashboard.png" alt="PrestaShop" %}
Use the PrestaShop dashboard
{% /image %}

## Global payment methods {% #global-payment-methods %}

You can turn on payment methods from the Stripe Dashboard. To increase conversion, Stripe dynamically displays the most relevant payment methods based on your customer's location and device. As Stripe adds new payment methods, you can turn them on without needing additional integrations. Use the connector to enable the following payment methods:

{% list %}
- **Credit and debit cards**: Visa, Mastercard, American Express, China UnionPay, Discover and Diners, Japan Credit Bureau (JCB), Cartes Bancaires
- **Mobile wallets**: Apple Pay, Google Pay, WeChat Pay, AliPay, GrabPay
- **Buy now, pay later and installments**: Klarna, Afterpay (Clearpay)
- **Bank debits**: ACH, SEPA debit, BECS direct debit, pre-authorized debit in Canada
- **Other popular payment methods**: Bancontact, EPS, iDEAL, Przelewy24, FPX, Boleto, OXXO
{% /list %}

{% comment %}
mpbagwell (12-20-22): Note to partialize some of this content due to overlap with Shopware 6.
{% /comment %}

{% see-also %}
* [Install the connector](/connectors/prestashop/installation)
* [Configure the connector](/connectors/prestashop/configuration)
* [Stripe Connector for PrestaShop FAQ](https://support.stripe.com/questions/prestashop)
{% /see-also %}
