---
title: Configure the Stripe Connector for PrestaShop
route: /connectors/prestashop/configuration
redirects:
- /plugins/prestashop/configuration

subtitle: Learn how to configure the Stripe Connector for PrestaShop.
stripe_products: []
---

To use Stripe with [PrestaShop](https://www.prestashop.com/en), you must [install](/connectors/prestashop/installation) and then configure the Stripe connector.


## Configure the connector {% #configure-connector %}

Use the PrestaShop dashboard to configure the connector.

1. Under **Modules**, select **Module Manager**.

2. On the **Modules** tab, for the **Stripe payment module**, click **Configure**.

3. Configure the Stripe Connector for PrestaShop:

  - [Connect to Stripe to accept payments](/connectors/prestashop/configuration#connect-stripe)
  - [Choose your payment form](/connectors/prestashop/configuration#payment-form)
  - [Customize the payment form](/connectors/prestashop/configuration#customize-payment-form)
  - [Collect your customer's postal code](/connectors/prestashop/configuration#postal-code)
  - [Choose how to capture funds](/connectors/prestashop/configuration#capture-funds)
  - [Save customer payment methods](/connectors/prestashop/configuration#payment-methods)
  - [Choose when the order is created](/connectors/prestashop/configuration#order-creation)


## Install the Stripe PrestaShop Commerce app

Use Stripe Apps to bolster security and simplify the use of distinct restricted keys for each integration with your Stripe account. The process of installing the Stripe App and acquiring the newly generated secret and publishable [keys](/keys) is essential for your integration with the PrestaShop Commerce connector. This approach eliminates the need to manually create your own restricted key or use a secret key. To integrate the PrestaShop Commerce app and reinforce your account's security infrastructure:

1. Navigate to the [Stripe App Marketplace](https://marketplace.stripe.com/), then click [Install the PrestaShop Commerce app](https://marketplace.stripe.com/apps/install/link/com.stripe.PrestaShop.commerce).
2. Select the Stripe account where you want to install the app.
3. Review and approve the app permissions, install the app in test mode or live mode, then click **Install**.
4. After you install the app, store the keys in a safe place where you won’t lose them. To help yourself remember where you stored it, you can [leave a note on the key in the Dashboard](/keys#reveal-an-api-secret-key-live-mode).
5. Use the newly generated publishable key and secret key to finish the Connector configuration.
6. To manage the app or generate new security keys after installation, navigate to the application settings page in [test mode](https://dashboard.stripe.com/test/settings/apps/com.stripe.PrestaShop.commerce) or [live mode](https://dashboard.stripe.com/settings/apps/com.stripe.PrestaShop.commerce).


## Connect to Stripe to accept payments {% #connect-stripe %}

Connect PrestaShop to your Stripe account to start accepting payments.

1. On the **Stripe Configure** page, click **Connect with Stripe**.
2. Navigate to the **Stripe Configure** page in the PrestaShop Dashboard, then paste the key from the Stripe PrestaShop app into the appropriate field.



## Choose your payment form {% #payment-form %}

Configure the payment form that displays to your customers during checkout. Under **Payment form settings**, you can choose from the following:

* **Integrated payment form**--The [Payment Element](/payments/payment-element) is an embeddable UI component that lets you accept 25+ payment methods with a single integration.

{% image src="images/prestashop/connector_payment_form_element.png" alt="Integrated payment form with Payment Element" border=true width=50 / %}

* **Redirect to Stripe**--[Stripe Checkout](/payments/checkout) lets you redirect your customers to a Stripe-hosted, customizable checkout page to finalize payment.

{% image src="images/prestashop/connector_payment_form_checkout.png" alt="Stripe-hosted checkout page" border=true width=50 / %}


## Customize the payment form {% #customize-payment-form %}

1. Click the **Integrated payment form** radio button to expose the customization options.
1. Choose a [layout](/payments/payment-element#layout) for the **Integrated payment form**:
  * **Accordion with radio buttons**
  * **Accordion without radio buttons**
  * **Tabs**
1. Choose where to position the payment form:
  * **On top of the Shopware payment methods**
  * **At the bottom of the Shopware payment methods**
  * **With the Shopware payment methods**
1. Choose a prebuilt theme that most closely resembles your website:
  * **Stripe**
  * **Flat**
  * **Night**
  * **None**

You can also [customize the look and feel of Checkout](/payments/checkout/customization) (**Redirect to Stripe**).

## Express Checkout Element

Express Checkout Element allows you to display one-click payment buttons with Link, Apple Pay, Google Pay, PayPal, and Amazon Pay.

Stripe sorts the payment buttons dynamically based on customer location, detected environment, and other optimized conversion factors.

On the backoffice, you can customize Express Checkout Element after you check **Enable Express Checkout**.

1. Specify where to display the one-click payment buttons:
  * On the **Product Page**
  * On the **Shopping Chart Page**
    {% image src="images/prestashop/express-checkout-product.png" alt="Express checkout at product level" border=true width=50 / %}
    {% image src="images/prestashop/express-checkout-cart.png" alt="Express checkout at cart level" border=true width=50 / %}
1. Choose different button themes and button types for Apple Pay, Google Pay and PayPal.

Both logged in and guest users can purchase through the Express Checkout buttons. Guest users will be able to enter their address through the payment interface.

## Collect your customer's postal code {% #postal-code %}

You can specify whether or not to collect your customer's postal code at checkout using the **Never collect the postal code** field. Stripe recommends collecting and verifying postal code information, which can help decrease the card decline rate.

* (Recommended) **Unselect** this field if you want to require a postal code at checkout. This applies to cards issued in Canada, the United Kingdom, or the United States.

* **Select** this field if you don't want to collect a postal code at checkout.


## Choose how to capture funds {% #capture-funds %}

You can specify how you want to authorize and capture funds using the **Enable separate authorization and capture** field.

* **Unselect** this field to use simultaneous authorization and capture. The issuing bank confirms that the cardholder can pay, and then transfers the funds automatically after confirmation.

* **Select** this field to use separate authorization and capture. The authorization occurs first, and the capture occurs later.

You can usually authorize a charge within a 7-day window.

To capture funds, do either of the following:

* In the PrestaShop dashboard, change the order's payment status from `Authorized` to the status you specify in the **Catch status** field. For example, you can use `Shipped` as the catch status. The capture occurs automatically when the status changes.

  If the capture is unsuccessful, the status changes to the specified value in the **Transition to the following order status if the authorization expires before being captured** field.

* In the Stripe Dashboard, under **Payments**, select **All payments**. On the **Uncaptured** tab, select the order and then click **Capture**.

## Save cutomer payment methods {% #Payment-methods %}

You can allow customers to save their payment details for faster checkout on future purchases by enabling the **Save payment methods at customer level** option.

## Choose when the order is created {% #order-creation %}

You can specify when to create the order during the payment process using the **Payment Flow** field:

* (Recommended) **Create the order after the payment is initiated** will create the order when the customer clicks the **Place Order** button.
* **Create the order after the payment is confirmed [legacy, not recommended]** will create the order after the payment is validated by Stripe.


## Refunds {% #refunds %}

To refund a payment, you need the Stripe Payment ID for the order.

1. In the PrestaShop dashboard, under **Orders**, select **Orders**.

2. Find the order you want to refund and copy the **Payment ID** under **Stripe**.

3. To initiate a full or partial refund, do the following:

  1. Go to the **Refund** tab on the **Stripe payment module**.

  2. In the **Stripe Payment ID** field, paste the payment ID.

  3. Select **Full refund** or **Partial refund**. If you want to initiate a partial refund, you must provide the amount to refund.

  4. Click **Request Refund**.

{% see-also %}
* [Overview](/connectors/prestashop)
* [Install the connector](/connectors/prestashop/installation)
  {% /see-also %}
