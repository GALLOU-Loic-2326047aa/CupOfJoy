<?php

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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../classes/StripeCapture.php';
require_once dirname(__FILE__) . '/../classes/StripeCustomer.php';

function upgrade_module_2_1_0($module)
{
    $installer = new Stripe_officialClasslib\Install\ModuleInstaller($module);
    $installer->installObjectModel('StripeCapture');
    $installer->installObjectModel('StripeCustomer');
    $installer->registerHooks();

    $handler = new Stripe_officialClasslib\Actions\ActionsHandler();

    $conveyorModel = new ConveyorModel();
    $conveyorModel->setContext(Context::getContext());
    $conveyorModel->setModule($module);
    $handler->setConveyor($conveyorModel);
    $handler->addActions('registerWebhookSignature');
    $handler->process('ConfigurationActions');

    return true;
}
