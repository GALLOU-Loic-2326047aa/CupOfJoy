/**
 * Copyright (c) since 2010 Stripe, Inc. (https://stripe.com)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Stripe <https://support.stripe.com/contact/email>
 * @copyright Since 2010 Stripe, Inc.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
let elements;
let stripe;
let expressCheckoutElement = null;
let cartId = null;
let productAttributeId = null;

$(function() {
  // Check if the product from product page is out of stock. If YES - unmount the Express Checkout elements, if NO - initialize it
  if (typeof prestashop !== 'undefined' && typeof prestashop.on === 'function') {
    prestashop.on('updatedProduct', (event) => {
      const productQuantity = typeof document.getElementById('stripe_product_quantity') !== 'undefined' ?
        (typeof document.getElementById('stripe_product_quantity').value !== 'undefined' ? document.getElementById('stripe_product_quantity').value : '0') : '';
      const quantity = typeof document.getElementById('quantity_wanted') !== 'undefined' ?
        (typeof document.getElementById('quantity_wanted').value !== 'undefined' ? document.getElementById('quantity_wanted').value : '') : '';

      if ((productQuantity === '0' && stripe_product_out_of_stock !== 1) || (parseInt(quantity) > parseInt(productQuantity) && stripe_product_out_of_stock !== 1)) {
        if (expressCheckoutElement) {
          expressCheckoutElement.unmount();
        }
      } else {
        initialize();
      }
    });
  }

  // Check if the product from cart is out of stock. If YES - unmount the Express Checkout elements, if NO - initialize it
  if (typeof prestashop !== 'undefined' && typeof prestashop.on === 'function') {
    prestashop.on('updateCart', (args) => {
      if (typeof args != 'undefined' && typeof args.resp != 'undefined' && (args.resp.errors !== '' || args.resp.cart.products.length === 0)) {
        if (expressCheckoutElement) {
          expressCheckoutElement.unmount();
        }
      } else {
        initialize();
      }
    });
  }
  if (express_checkout === '0' || typeof stripe_express_amount == 'undefined' || stripe_express_amount === 0) {
      return;
  }

  initialize();
});

async function initialize() {
  if(typeof Stripe != 'undefined') {
    stripe = Stripe(stripe_pk);
  } else {
    return;
  }

  let amount;
  let $pageName;
  let $quantity;

  if(document.body.id === 'product')
  {
    const productQuantity = document.getElementById('stripe_product_quantity') !== null
      ? (document.getElementById('stripe_product_quantity').value !== null ? document.getElementById('stripe_product_quantity').value : '0') : '0';
    $quantity = document.getElementById('quantity_wanted') !== null ? document.getElementById('quantity_wanted').value : '0';
    if (parseInt(productQuantity) <= 0 && stripe_product_out_of_stock !== 1) {
      return;
    }
    $pageName = 'product';

    amount = stripe_express_amount * parseInt($quantity);
  } else if (document.body.id === 'cart') {
    if(typeof stripe_out_of_stock !== 'undefined' && stripe_out_of_stock)
    {
      return;
    }
    $pageName = 'cart';
    amount = stripe_express_amount;
  } else {
    return;
  }

  let $classSelector;
  let $expressCheckoutElement = document.getElementById('stripe-express-checkout-element');
  let $errorMessage = document.getElementById('stripe-error-message');

  let locations = stripe_locations.toString();

  if(document.body.id === 'product' && locations.includes('product')) {
    $classSelector = document.querySelector('.product-add-to-cart');
    $classSelector.insertAdjacentElement('beforeend', $expressCheckoutElement);
    $classSelector.insertAdjacentElement('beforeend', $errorMessage);
  } else if (document.body.id === 'cart' && locations.includes('cart')) {
    $classSelector = document.querySelector('.cart-detailed-totals');
    $classSelector.insertAdjacentElement('afterend', $errorMessage);
    $classSelector.insertAdjacentElement('afterend', $expressCheckoutElement);
    $expressCheckoutElement.style = 'padding: 0.5rem 1.25rem .5rem';
  }

  let elementOptions = {
    buttonType: {
      googlePay: google_pay_button_type ? google_pay_button_type : 'plain',
      applePay: apple_pay_button_type ? apple_pay_button_type : 'plain',
      paypal: pay_pal_button_type ? pay_pal_button_type : 'paypal',
    },
    buttonTheme: {
      googlePay: google_pay_button_theme ? google_pay_button_theme : 'black',
      applePay: apple_pay_button_theme ? apple_pay_button_theme : 'black',
      paypal: pay_pal_button_theme ? pay_pal_button_theme : 'black',
    }
  }

  const appearance = {
    variables: {
      borderRadius: '0px',
    }
  }

  const options = {
    mode: 'payment',
    amount: amount,
    currency: stripe_express_currency_iso,
    appearance,
  }

  elements = stripe.elements(options);

  expressCheckoutElement = elements.create('expressCheckout', elementOptions);
  expressCheckoutElement.mount('#stripe-express-checkout-element', {
    phoneNumberRequired: true
  });

  let productNotSelected = document.body.id === 'product' && !locations.includes('product');
  let cartNotSelected = document.body.id === 'cart' && !locations.includes('cart');

  if (productNotSelected || cartNotSelected)
  {
    expressCheckoutElement.unmount();
  }

  expressCheckoutElement.on('confirm', async (event) => {
    const {error: submitError} = await elements.submit();
    if (submitError) {
      handleError(submitError);
      return;
    }

    let $productId = typeof stripe_express_product_id !== 'undefined' ? stripe_express_product_id : '';
    let idProductAttribute = document.getElementById('stripe_product_attribute_info') !== null ? document.getElementById('stripe_product_attribute_info').value : '';
    // Create the PaymentIntent and obtain clientSecret
    const res = await fetch(stripe_create_intent, {
      method: 'POST',
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({
        amount: amount,
        currency: stripe_express_currency_iso,
        event: event,
        productId: $productId,
        productAttributeId: idProductAttribute,
        productQuantity: $quantity,
        pageName: $pageName,
        cartId: cartId
      }),
    });

    let result = await res.json();
    if (result.error) {
      handleError(result);
      return;
    }
    let clientSecret = result.intent.client_secret;
    let returnUrl = result.stripe_express_return_url;

    const {error} = await stripe.confirmPayment({
      // `elements` instance used to create the Express Checkout Element
      elements,
      // `clientSecret` from the created PaymentIntent
      clientSecret,
      confirmParams: {
        return_url: returnUrl,
      },
    });

    if (error) {
      // This point is only reached if there's an immediate error when
      // confirming the payment. Show the error to your customer (for example, payment details incomplete)
      handleError(error);
    } else {
      // The payment UI automatically closes with a success animation.
      // Your customer is redirected to your `return_url`.
    }
  });

  let carriers = [];
  carriers.push({
    id: '10001',
    displayName: 'Initial carrier',
    amount: 0
  });

  expressCheckoutElement.on('click', (event) => {
    const options = {
      emailRequired: true,
      phoneNumberRequired: true,
      shippingAddressRequired: true,
      shippingRates: carriers
    };
    if(document.body.id === 'product')
    {
      let productQuantity = typeof document.getElementById('quantity_wanted') !== 'undefined' ?
        (typeof document.getElementById('quantity_wanted').value !== 'undefined' ? document.getElementById('quantity_wanted').value : '') : '';
      $quantity = productQuantity;
      let amountProductString = document.querySelector('.current-price span').innerHTML;
      let findAmount = amountProductString.replace(/[^0-9.,]/g, '');
      findAmount = findAmount.replace(/,/g, '');
      let productAmount = Math.round(parseFloat(findAmount) * 100);
      amount = productAmount * parseInt(productQuantity);
      elements.update({amount: amount});
    }
    if (document.body.id === 'cart') {
      let cartTotalString = document.querySelector('.cart-total .value').innerHTML;
      let str = cartTotalString.replace(/[^0-9.,]/g, '');
      str = str.replace(/,/g, '');
      let productTotalAmount = Math.round(parseFloat(str) * 100);
      //get shipping amount
      let shippingElement = document.querySelector('#cart-subtotal-shipping .value');
      let shippingTotal = 0;
      if (shippingElement) {
        let shippingString = shippingElement.innerHTML;
        let shippingMatch = /[0-9](.+)/.exec(shippingString);

        if (shippingMatch && shippingMatch[0]) {
          let shipping = shippingMatch[0].replace(',', '.');
          shippingTotal = Math.round(((parseFloat(shipping) * 100)));
        }
      }
      //adjust amount when there is a shipping fee
      amount = productTotalAmount - shippingTotal;

      elements.update({amount: amount});
    }
    event.resolve(options);
  });

  expressCheckoutElement.on('shippingaddresschange', async (event) => {
    let $prodId = typeof stripe_express_product_id !== 'undefined' ? stripe_express_product_id : '';
    let idProductAttribute = document.getElementById('stripe_product_attribute_info') !== null ? document.getElementById('stripe_product_attribute_info').value : '';

    const response = await fetch(stripe_calculate_shipping, {
      method: 'POST',
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({
        shippingAddress: event.address,
        productId: $prodId,
        idProductAttribute: idProductAttribute,
        productQuantity: $quantity,
        expressCheckoutType: document.body.id
      })
    });

    const result = await response.json();

    let carriers = [];
    cartId = cartId !== null ? cartId : result.cartId;

    if (typeof result['carriers'] === 'undefined' || result['carriers'].length === 0) {
      handleError({error: true, message: 'Shipping zone unavailable'});
      return;
    }

    let precision = 2;
    if (result.hasOwnProperty('precision')) {
      precision = result['precision'];
    }
    if (result['updatedCartAmount']) {
      amount = result['updatedCartAmount'];
    }

    productAttributeId = result.productAttributeId;

    //retrieve default carrier ID from response
    let defaultCarrierId = result.defaultCarrierId ? result.defaultCarrierId.toString() : null;

    let totalAmount = amount;
    let i = 1;

    //sort carriers to ensure the default carrier appears first
    result['carriers'].sort((a, b) => {
      if (a.id_carrier.toString() === defaultCarrierId) return -1;
      if (b.id_carrier.toString() === defaultCarrierId) return 1;
      return 0;
    });

    result['carriers'].forEach((carrier) => {
      let carrierAmount = ((carrier['price'] * Math.pow(10, precision)) | 0);

      // If carrier has free shipping, set shipping cost to 0
      if (carrier['is_free'] === true) {
        carrierAmount = 0;
      }

      //ensure the first carrier is the default one
      if (i === 1) {
        totalAmount = amount + carrierAmount;
      }

      if (i < 10) {
        carriers.push({
          id: carrier['id_carrier'].toString(),
          displayName: carrier['name'],
          amount: carrierAmount
        });
      }
      i++;
    });

    const options = {
      shippingRates: carriers
    };
    elements.update({amount: totalAmount});
    event.resolve(options);
  });

  expressCheckoutElement.on('shippingratechange', async (event) => {
    let shippingRateAmount = event.shippingRate.amount;
    let totalAmount = amount + shippingRateAmount;
    elements.update({amount: totalAmount});
    event.resolve();
  });
}

const handleError = (error) => {
  const messageContainer = document.querySelector('#stripe-error-message');
  messageContainer.textContent = error.message;
  messageContainer.style = 'color: red';
}
