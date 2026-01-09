<?php
require_once('../../config/config.inc.php');
require_once('../../init.php');
require_once('ps_stripe_subscriptions.php');

$module = Module::getInstanceByName('ps_stripe_subscriptions');
$module->initStripeApi();

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Event::constructFrom(json_decode($payload, true));
} catch(\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
}

if ($event->type === 'invoice.paid') {
    $invoice = $event->data->object;
    $stripe_customer_id = $invoice->customer;

    // On retrouve l'ID du client PrestaShop via l'ID Stripe
    $id_customer = Db::getInstance()->getValue('
        SELECT id_customer 
        FROM '._DB_PREFIX_.'stripe_customer_link 
        WHERE id_stripe_customer = "'.pSQL($stripe_customer_id).'"'
    );

    if ($id_customer) {
        $customer = new Customer($id_customer);

        $module->validateOrder(
            (int)0,
            (int)Configuration::get('PS_OS_PAYMENT'),
            (float)($invoice->amount_paid / 100),
            $module->displayName,
            'Renouvellement automatique Stripe : ' . $invoice->subscription,
            [],
            (int)Context::getContext()->currency->id,
            false,
            $customer->secure_key
        );
    }
}

http_response_code(200);
