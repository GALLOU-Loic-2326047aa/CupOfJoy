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

namespace StripeOfficial\Classes\services\MainGetContent\Actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Prestashop\ModuleLibMboInstaller\DependencyBuilder;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShop\PsAccountsInstaller\Installer\Exception\InstallerException;
use StripeOfficial\Classes\StripeProcessLogger;

class RegisterCloudSyncAction extends BaseAction
{
    public function execute()
    {
        $mboInstaller = new DependencyBuilder($this->module);
        $shopGroupId = \Stripe_official::getShopGroupIdContext();
        $shopId = \Stripe_official::getShopIdContext();
        $wantsToUseCloudSync = \Configuration::get(\Stripe_official::WANTS_TO_USE_CLOUDSYNC, null, $shopGroupId, $shopId, true);
        if (!$wantsToUseCloudSync && !$mboInstaller->areDependenciesMet()) {
            return true;
        }
        if (!$mboInstaller->areDependenciesMet()) {
            $dependencies = $mboInstaller->handleDependencies();
            $this->module->getContext()->smarty->assign('dependencies', $dependencies);

            return $this->module->display($this->module->getPathUri(), 'views/templates/admin/dependency_builder.tpl');
        }

        $this->module->getContext()->smarty->assign('module_dir', $this->module->getPathUri());
        $moduleManager = ModuleManagerBuilder::getInstance()->build();

        /*********************
         * PrestaShop Account *
         * *******************/

        $accountsService = null;
        try {
            $accountsFacade = $this->module->getService('stripe_official.ps_accounts_facade');
            $accountsService = $accountsFacade->getPsAccountsService();
        } catch (InstallerException $e) {
            StripeProcessLogger::logError('getPsAccountsService exception: ' . $e->getMessage(), 'stripe_official');
            $accountsInstaller = $this->module->getService('stripe_official.ps_accounts_installer');
            $accountsInstaller->install();
            $accountsFacade = $this->module->getService('stripe_official.ps_accounts_facade');
            $accountsService = $accountsFacade->getPsAccountsService();
        }

        try {
            \Media::addJsDef([
                'contextPsAccounts' => $accountsFacade->getPsAccountsPresenter()
                    ->present($this->module->name),
            ]);

            // Retrieve the PrestaShop Account CDN
            $this->module->getContext()->smarty->assign('urlAccountsCdn', $accountsService->getAccountsCdn());
        } catch (\Exception $e) {
            $this->module->getContext()->controller->errors[] = $e->getMessage();
            StripeProcessLogger::logError('getPsAccountsPresenter exception: ' . $e->getMessage(), 'stripe_official');

            return '';
        }

        if ($moduleManager->isInstalled('ps_eventbus')) {
            $eventbusModule = \Module::getInstanceByName('ps_eventbus');
            if (version_compare($eventbusModule->version, '1.9.0', '>=')) {
                $eventbusPresenterService = $eventbusModule->getService('PrestaShop\Module\PsEventbus\Service\PresenterService');

                $this->module->getContext()->smarty->assign('urlCloudsync', 'https://assets.prestashop3.com/ext/cloudsync-merchant-sync-consent/latest/cloudsync-cdc.js');

                \Media::addJsDef([
                    'contextPsEventbus' => $eventbusPresenterService->expose($this->module, ['info', 'modules', 'themes', 'carts', 'orders', 'stores']),
                ]);
            }
        }

        return true;
    }
}
