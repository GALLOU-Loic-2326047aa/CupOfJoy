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
let createBillingDetails;
let createElementsDetails;
let paymentElement;
let stripe;

window.onerror = async function(msg, url, line, col, error) {
  if (
    msg.includes("stripe") || msg.includes("checkout.js") || msg.includes("expressCheckout.js") ||
    error.stack.toString().includes("stripe") || error.stack.toString().includes("checkout.js") || error.stack.toString().includes("expressCheckout.js")
  ) {
    let msg = error.stack.toString();
    await fetch(stripe_log_js_error, {
      method: 'POST',
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({
        msg: msg
      }),
    });
  }
}

function setCookie(name, value, daysToLive) {
  // Encode value in order to escape semicolons, commas, and whitespace
  var cookie = name + "=" + encodeURIComponent(value);

  if(typeof daysToLive === "number") {
    /* Sets the max-age attribute so that the cookie expires
    after the specified number of days */
    cookie += "; max-age=" + (daysToLive*24*60*60);

    document.cookie = cookie;
  }
}

function getCookie(name) {
  // Split cookie string and get all individual name=value pairs in an array
  var cookieArr = document.cookie.split(";");

  // Loop through the array elements
  for(var i = 0; i < cookieArr.length; i++) {
    var cookiePair = cookieArr[i].split("=");

    /* Removing whitespace at the beginning of the cookie name
    and compare it with the given string */
    if(name === cookiePair[0].trim()) {
      // Decode the cookie value and return
      return decodeURIComponent(cookiePair[1]);
    }
  }

  // Return null if not found
  return null;
}

$(function() {
  if (stripe_payment_elements_enabled === '0' || document.body.id !== 'checkout') {
    return;
  }
  let radioButtons = document.querySelectorAll('input[name="payment-option"]');
  let $paymentForm = document.querySelector('#js-stripe-payment-form');
  let $placeOrderButton = document.querySelector('#payment-confirmation button[type="submit"]');
  let paymentFormInput = document.querySelector('input[data-module-name="stripe_official"]');
  let label = paymentFormInput !== null ? paymentFormInput.parentElement.parentElement : null;

  setCookie('ps.pm', true, 1);
  if (paymentFormInput && paymentFormInput.checked) {
    setCookie('ps.pm', false, 1);
  }

  if (radioButtons.length === 1 && paymentFormInput) {
    $paymentForm.addEventListener("submit", handleSubmit);
    initialize();
    $placeOrderButton.addEventListener('click', handleClick);
  } else {
    radioButtons.forEach(function (input) {
      input.addEventListener("change", function() {

        if(input.dataset.moduleName === 'stripe_official' && input.checked && $paymentForm) {
          if (stripe_payment_elements_enabled === '1') {
            $paymentForm.addEventListener("submit", handleSubmit);
            setCookie('ps.pm', false, 1);
            initialize();
          }

          $placeOrderButton.addEventListener('click', handleClick);
        } else {
          setCookie('stripe.collapsed', 'false', 1);
          setCookie('ps.pm', true, 1);
          $placeOrderButton.removeEventListener('click', handleClick);
          if(paymentElement){
            paymentElement.collapse();
            setTimeout(function () {
              let $stripeCollapse = getCookie('stripe.collapsed');
              if ($stripeCollapse === 'false') {
                label.setAttribute('style', 'display:block');
                paymentElement.unmount();
              }
            }, 5);
          }
        }
      });
      if(input.dataset.moduleName === 'stripe_official' && input.checked && $paymentForm) {
          $placeOrderButton.addEventListener('click', handleClick);
      }
    })
  }

  let oneButtonChecked = false;

  for(const button of radioButtons) {
    if (button.checked) {
      oneButtonChecked = true;
    }
  }

  if (!oneButtonChecked && paymentFormInput) {
    paymentFormInput.click();
  }
});

function handleClick(e) {
  if (stripe_payment_elements_enabled === '1') {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    let $paymentForm = document.querySelector('#js-stripe-payment-form');
    let $stripeSubmit = $paymentForm.querySelector('button[type="submit"]');
    $stripeSubmit.click();
    let $placeOrderButton = document.querySelector('#payment-confirmation button[type="submit"]');
    let $SpinnerWrapper = document.getElementById('stripeSpinnerWrapper');
    $placeOrderButton.setAttribute("disabled", "disabled");
    $SpinnerWrapper.style.display = 'flex';
  }
}

// Fetches a payment intent and captures the client secret
async function initialize() {
  let $stripeCollapsed = getCookie('stripe.collapsed');

// This is your test publishable API key.
  stripe = Stripe(stripe_pk);
//Create elements on the payment page
  const createElements = await fetch(stripe_create_elements, {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify(),
  }).then((r) => r.json());

  createElementsDetails = createElements.element
  createBillingDetails = createElements.billing_details;

  let $classSelector = document.querySelector('.payment-options ');
  let $paymentForm = document.querySelector('#js-stripe-payment-form');
  $paymentForm.style = '';
  let paymentFormInput = document.querySelector('input[data-module-name="stripe_official"]');
  let label = paymentFormInput.parentElement.parentElement;

  if ($stripeCollapsed === 'false' && paymentElement && stripe_position !== 'middle') {
    label.setAttribute('style', 'display:none')
    paymentElement.mount("#js-stripe-payment-element");
    return;
  }

  let position = stripe_position ? stripe_position : "top";

  let appearance = {
    rules:
      {
        '.AccordionItem': {
          borderColor: '#fff',
          boxShadow: 'false',
        },
        '.AccordionItem--selected': {
          borderColor: '#fff',
          boxShadow: 'false',
        },
      }
  };

  appearance.theme = stripe_theme ? stripe_theme : (appearance.theme !== 'undefined' ? appearance.theme : "stripe");

  let layouts = {
    'tabs':
      {
        type: 'tabs',
        defaultCollapsed: false
      },
    'accordion':
      {
        type: 'accordion',
        defaultCollapsed: false,
        radios: false,
        spacedAccordionItems: true,
        visibleAccordionItemsCount: 0
      },
    'radio':
      {
        type: 'accordion',
        defaultCollapsed: false,
        radios: true,
        spacedAccordionItems: true,
        visibleAccordionItemsCount: 0
      }
  }

  let formLayout = layouts[stripe_layout] ? layouts[stripe_layout] : layouts['radio'];

  if (stripe_payment_elements_enabled === '0') {
    formLayout.defaultCollapsed = true;
  }

  if (formLayout === layouts['radio'] && position === 'top') {
    $paymentForm.style = "margin-left: -17px"
  } else if (formLayout === layouts['radio'] && position === 'bottom') {
    $paymentForm.style = "padding-bottom: 15px; margin-left: -17px; margin-top: -25px"
  } else if (position === 'bottom') {
    $paymentForm.style = "padding-bottom: 15px; margin-top: -25px"
  } else if (position === 'middle') {
    $paymentForm.style = "padding-bottom: 15px"
  }

  if(position === 'top') {
    $classSelector.insertAdjacentElement('afterbegin', $paymentForm)
    label.setAttribute('style', 'display:none');
  } else if (position === 'bottom') {
    $classSelector.insertAdjacentElement('beforeend', $paymentForm);
    label.setAttribute('style', 'display:none');
  } else if (position === 'middle') {
    $paymentForm;
  }

  const options = {
    mode: createElements.element.mode,
    amount: createElements.element.amount,
    currency: createElements.element.currency.toLowerCase(),
    locale: stripe_locale,
    appearance: appearance,
    paymentMethodCreation: 'manual',
    customerSessionClientSecret: createElements.customer_session_client_secret
  };
  if (save_payment_method === 'off_session') {
    options.setupFutureUsage =  'off_session';
  }

  elements = stripe.elements(options);

  let elementOptions = {
    defaultValues: {
      billingDetails: {
        address: {
          country: createBillingDetails.billing_details.address.country,
          postal_code: createBillingDetails.billing_details.address.postal_code
        },
        email: createBillingDetails.billing_details.email,
        name: createBillingDetails.billing_details.name
      }
    },
    layout: formLayout
  };

  paymentElement = elements.create("payment", elementOptions);
  paymentElement.mount("#js-stripe-payment-element");

  let $paymentElementReady = false;
  paymentElement.on('ready', function(event) {
    $paymentElementReady = true;
  });

  let $wasCollapsedBefore = false;
  paymentElement.on('change', function(event) {
    var $psPm = getCookie('ps.pm');
    if($psPm && $psPm === 'true' && !event.collapsed && !$wasCollapsedBefore){
      label.setAttribute('style', 'display:block');
      paymentElement.unmount();
    } else {
      setCookie('ps.pm', false, 1);
      $psPm = 'false';
      setCookie('stripe.collapsed', 'true', 1);
    }
    $wasCollapsedBefore = event.collapsed;

    if(!paymentFormInput.checked && $paymentElementReady && !event.collapsed && $psPm && $psPm === 'false') {
      paymentElement.unmount();
      paymentFormInput.click();
    }
  });
}

const handleError = (error) => {
  let $placeOrderButton = document.querySelector('#payment-confirmation button[type="submit"]');
  $placeOrderButton.removeAttribute("disabled");
  let $SpinnerWrapper = document.getElementById('stripeSpinnerWrapper');
  $SpinnerWrapper.style.display = 'none';
  const messageContainer = document.querySelector('#error-message');
  if (typeof messageContainer !== undefined && messageContainer) {
    messageContainer.textContent = error.message;
  }
}

async function generateSHA1(input) {
  // Convert the input string to an ArrayBuffer
  const encoder = new TextEncoder();
  const data = encoder.encode(input);

  // Generate the SHA1 hash
  const hashBuffer = await crypto.subtle.digest("SHA-1", data);

  // Convert the ArrayBuffer to a hex string
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  const hashHex = hashArray.map((byte) => byte.toString(16).padStart(2, "0")).join("");

  return hashHex;
}

async function handleSubmit(e) {
  e.preventDefault();
  e.stopPropagation();
  e.stopImmediatePropagation();
//When the customer presses “Submit”, create the PaymentIntent server-side and confirm client-side
  // Trigger form validation and wallet collection
  const {error: submitError} = await elements.submit();
  if (submitError) {
    handleError(submitError);
    return;
  }

  const confirmationTokenParams = await stripe.createConfirmationToken({elements,
    params: createBillingDetails
  });
  let customer = createElementsDetails.customer ? createElementsDetails.customer : '';
  let verifyToken = await generateSHA1(customer + confirmationTokenParams.confirmationToken.id);

  if (confirmationTokenParams.confirmationToken && confirmationTokenParams.confirmationToken.id) {
    let redirect = handle_order_action_url+'?confirmationToken='+confirmationTokenParams.confirmationToken.id+'&verifyToken='+verifyToken;
    if (handle_order_action_url.includes('?')) {
      redirect = handle_order_action_url+'&confirmationToken='+confirmationTokenParams.confirmationToken.id+'&verifyToken='+verifyToken;
    }
    window.location.href = redirect;
  }
}
