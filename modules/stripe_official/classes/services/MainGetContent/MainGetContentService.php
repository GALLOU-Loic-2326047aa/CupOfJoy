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

namespace StripeOfficial\Classes\services\MainGetContent;

use StripeOfficial\Classes\services\MainGetContent\Actions\BuildContextAction;
use StripeOfficial\Classes\services\MainGetContent\Actions\CheckWebhookAction;
use StripeOfficial\Classes\services\MainGetContent\Actions\IsCorrectlyConfiguredAction;
use StripeOfficial\Classes\services\MainGetContent\Actions\IsSslEnabledAction;
use StripeOfficial\Classes\services\MainGetContent\Actions\LoginAction;
use StripeOfficial\Classes\services\MainGetContent\Actions\RefundAction;
use StripeOfficial\Classes\services\MainGetContent\Actions\RegisterCloudSyncAction;

if (!defined('_PS_VERSION_')) {
    exit;
}

class MainGetContentService
{
    protected $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function getContent()
    {
        $actions = [
            new IsSslEnabledAction($this->module),
            new LoginAction($this->module),
            new IsCorrectlyConfiguredAction($this->module),
            new CheckWebhookAction($this->module),
            new RefundAction($this->module),
            new BuildContextAction($this->module),
            new RegisterCloudSyncAction($this->module),
        ];

        foreach ($actions as $action) {
            $result = $action->execute();
            if ($result !== true) {
                return $result;
            }
        }

        return $this->module->display($this->module->getPathUri(), 'views/templates/admin/main.tpl');
    }
}
