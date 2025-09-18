<?php
/**
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author B-Ant Digital Solutions Zrt. <addons@blueant-solutions.com>
 *  @copyright 2019-2025 B-Ant Digital Solutions Zrt.
 *  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Bant\BantGorgias\Api\Api;
use Prestashop\ModuleLibMboInstaller\DependencyBuilder;
use PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer;
use Prestashop\Prestashop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use PrestaShop\PsAccountsInstaller\Installer\Exception\InstallerException;

class BantGorgias extends Module
{
    const TOKEN = 'ZHWFhNR@R3WdyfZboYDjzC3Q4rKrwty6';
    const GORGIAS_CHAT_URL = 'https://config.gorgias.chat';

    public $container;

    public function __construct()
    {
        $this->name = 'bantgorgias';
        $this->tab = 'administration';
        $this->version = '2.0.2';
        $this->author = 'Blueant Solutions';
        $this->module_key = 'f8169b69226477f716e30561c6f414dc';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Official Gorgias Integration - Helpdesk');
        $this->description = $this->l('Sync your PrestaShop store orders with Gorgias, an all-in-one helpdesk software for Ecommerce. It lets you access all your customer data, handle refunds, and create rules/macros for efficient customer support');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => _PS_VERSION_];

        if ($this->container === null) {
            $this->container = new ServiceContainer(
                $this->name,
                $this->getLocalPath()
            );
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws Exception
     */
    public function install(): bool
    {
        $configurationService = new Configuration();

        if (parent::install()) {
            $this->addHtaccessRule();

            $hookRes = $this->registerHook('displayBeforeBodyClosingTag') && $this->registerHook('actionHtaccessCreate');

            if (!$hookRes) {
                return false;
            }

            try {
                $configurationService->set('BANTGORGIAS_API_KEY', '');
                $configurationService->set('BANTGORGIAS_DOMAIN', '');
                $configurationService->set('BANTGORGIAS_ORDERSTATE_CANCELLED', '');
                $configurationService->set('BANTGORGIAS_ORDERSTATE_REFUNDED', '');
                $configurationService->set('BANTGORGIAS_ADMIN_URL', '');
                $configurationService->set('BANTGORGIAS_CHAT', '');
                $configurationService->set('BANTGORGIAS_AUTHUSER', '');
                $configurationService->set('BANTGORGIAS_AUTHPASS', '');
                $configRes = true;
            } catch (PrestaShopException|Exception $e) {
                $configRes = false;
            }

            if (!$configRes) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function uninstall(): bool
    {
        $configurationService = new Configuration();
        $moduleManager = ModuleManagerBuilder::getInstance()->build();

        if (parent::uninstall()) {
            $shopUuid = '';
            $shopDomain = $this->context->shop->domain_ssl;
            $api = new Api($shopUuid, $shopDomain, 'api/prestashop/disconnect');
            $api->post([
                'type' => 'uninstall',
                'shop_url' => Tools::getShopProtocol() . $shopDomain,
                'module_status' => $moduleManager->isEnabled($this->name),
                'api_key' => $configurationService->get('BANTGORGIAS_API_KEY', ''),
            ]);
            $res = $this->unRegisterHook(Hook::getIdByName('displayBeforeBodyClosingTag')) && $this->unRegisterHook(Hook::getIdByName('actionHtaccessCreate'));
            try {
                $configurationService->remove('BANTGORGIAS_API_KEY');
                $configurationService->remove('BANTGORGIAS_DOMAIN');
                $configurationService->remove('BANTGORGIAS_ORDERSTATE_CANCELLED');
                $configurationService->remove('BANTGORGIAS_ORDERSTATE_REFUNDED');
                $configurationService->remove('BANTGORGIAS_ADMIN_URL');
                $configurationService->remove('BANTGORGIAS_CHAT');
                $configurationService->remove('BANTGORGIAS_AUTHUSER');
                $configurationService->remove('BANTGORGIAS_AUTHPASS');

                $configRes = true;
            } catch (PrestaShopException|Exception $e) {
                $configRes = false;
            }

            return $res && $configRes;
        }

        return false;
    }

    /**
     * @throws SmartyException
     * @throws Exception
     */
    public function getContent(): bool|string
    {
        // Load dependencies manager
        $mboInstaller = new DependencyBuilder($this);

        if (!$mboInstaller->areDependenciesMet()) {
            $dependencies = $mboInstaller->handleDependencies();

            $this->smarty->assign('dependencies', $dependencies);

            return $this->display(__FILE__, 'views/templates/admin/dependency_builder.tpl');
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $moduleManager = ModuleManagerBuilder::getInstance()->build();

        $accountsService = null;

        try {
            $accountsFacade = $this->getService('bantgorgias.ps_accounts_facade');
            $accountsService = $accountsFacade->getPsAccountsService();
        } catch (InstallerException $e) {
            $accountsInstaller = $this->getService('bantgorgias.ps_accounts_installer');
            $accountsInstaller->install();
            $accountsFacade = $this->getService('bantgorgias.ps_accounts_facade');
            $accountsService = $accountsFacade->getPsAccountsService();
        }

        try {
            Media::addJsDef([
                'contextPsAccounts' => $accountsFacade->getPsAccountsPresenter()->present($this->name),
            ]);

            $this->context->smarty->assign('urlAccountsCdn', $accountsService->getAccountsCdn());
        } catch (Exception $e) {
            $this->context->controller->errors[] = $e->getMessage();

            return '';
        }

        if ($moduleManager->isInstalled('ps_eventbus')) {
            $eventbusModule = Module::getInstanceByName('ps_eventbus');
            if (version_compare($eventbusModule->version, '1.9.0', '>=')) {
                $eventbusPresenterService = $eventbusModule->getService('PrestaShop\Module\PsEventbus\Service\PresenterService');
                $this->context->smarty->assign('urlCloudsync', 'https://assets.prestashop3.com/ext/cloudsync-merchant-sync-consent/latest/cloudsync-cdc.js');

                Media::addJsDef([
                    'contextPsEventbus' => $eventbusPresenterService->expose($this, ['info', 'modules', 'themes']),
                ]);
            }
        }

        $accountsData = [];

        if ($accountsFacade->getPsAccountsService()->isAccountLinked()) {
            $accountsData = $this->getData($accountsFacade->getPsAccountsPresenter()->present($this->name), $accountsFacade->getPsAccountsService()->getOrRefreshToken());
        }

        $this->setAdminUrl();

        $configurationService = new Configuration();

        $this->context->smarty->assign([
            'isLinked' => $accountsFacade->getPsAccountsService()->isAccountLinked(),
            'accountsData' => json_encode($accountsData),
            'apiUrl' => $this->context->link->getModuleLink($this->name, 'api'),
            'shopDomain' => $this->context->shop->domain_ssl,
            'shopUuid' => $accountsData['currentShop']['uuid'] ?? '',
            'api_key' => $configurationService->get('BANTGORGIAS_API_KEY', '', ShopConstraint::shop($this->context->shop->id)),
            'domain' => $configurationService->get('BANTGORGIAS_DOMAIN', '', ShopConstraint::shop($this->context->shop->id)),
            'currentOrderState' => $configurationService->get('BANTGORGIAS_ORDERSTATE_CANCELLED', '', ShopConstraint::shop($this->context->shop->id)),
            'currentRefundOrderState' => $configurationService->get('BANTGORGIAS_ORDERSTATE_REFUNDED', '', ShopConstraint::shop($this->context->shop->id)),
            'orderStates' => OrderState::getOrderStates($this->context->language->id),
            'chat' => $configurationService->get('BANTGORGIAS_CHAT', '', ShopConstraint::shop($this->context->shop->id)),
            'authUser' => $configurationService->get('BANTGORGIAS_AUTHUSER', '', ShopConstraint::shop($this->context->shop->id)),
            'authPass' => $configurationService->get('BANTGORGIAS_AUTHPASS', '', ShopConstraint::shop($this->context->shop->id)),
        ]);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
    }

    public function getService($serviceName): ?object
    {
        if ($this->container === null) {
            $this->container = new ServiceContainer(
                $this->name,
                $this->getLocalPath()
            );
        }

        return $this->container->getService($serviceName);
    }

    public function hookActionHtaccessCreate(): bool
    {
        return $this->addHtaccessRule();
    }

    /**
     * @throws SmartyException
     * @throws PrestaShop\PrestaShop\Core\Domain\Shop\Exception\ShopException
     */
    public function hookDisplayBeforeBodyClosingTag(): string
    {
        $configurationService = new Configuration();

        $chat = $configurationService->get('BANTGORGIAS_CHAT', '', ShopConstraint::shop($this->context->shop->id));

        if (!$chat || !str_contains($chat, self::GORGIAS_CHAT_URL)) {
            return '';
        }

        $this->context->smarty->assign([
            'chat' => $this->sanitizeGorgiasScript($chat),
        ]);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/hook/chat.tpl');
    }

    public function addHtaccessRule(): bool
    {
        $path = _PS_ROOT_DIR_ . '/.htaccess';

        // Validate path to prevent path traversal
        $realPath = realpath(dirname($path));
        $expectedPath = realpath(_PS_ROOT_DIR_);

        if ($realPath === false || $expectedPath === false || !str_starts_with($realPath, $expectedPath)) {
            return false;
        }

        // Ensure the filename is exactly .htaccess
        if (basename($path) !== '.htaccess') {
            return false;
        }

        if (!file_exists($path)) {
            return false;
        }

        $htaccess = Tools::file_get_contents($path);

        $rule = "\nSetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=$1\n";

        if (mb_strpos($htaccess, $rule) === false) {
            file_put_contents($path, $rule, FILE_APPEND);
        }

        return true;
    }

    public function getData($accountsData, $token): array
    {
        if (isset($accountsData['currentShop']['publicKey'])) {
            unset($accountsData['currentShop']['publicKey']);
        }

        return [
            'currentShop' => $accountsData['currentShop'],
            'user' => $accountsData['user'],
            'shops' => $accountsData['shops'],
            'isShopContext' => $accountsData['isShopContext'],
            'token' => $token,
        ];
    }

    /**
     * @throws Exception
     */
    public function setAdminUrl(): bool
    {
        $configurationService = new Configuration();

        $adminUrl = $configurationService->get('BANTGORGIAS_ADMIN_URL', '', ShopConstraint::shop($this->context->shop->id));
        if (!$adminUrl) {
            try {
                $configurationService->set('BANTGORGIAS_ADMIN_URL', mb_substr(Context::getContext()->link->getAdminLink('index'), 0, strpos(Context::getContext()->link->getAdminLink('index'), '?')), ShopConstraint::shop($this->context->shop->id));

                return true;
            } catch (PrestaShopException $e) {
                return false;
            }
        }

        return true;
    }

    private function sanitizeGorgiasScript($script): string
    {
        if (empty($script)) {
            return '';
        }

        // Allowed domains for Gorgias scripts
        $allowedDomains = [
            'config.gorgias.chat',
            'bundle.dyn-rev.app',
            'gorgias.chat',
        ];

        // Remove any potential script injection outside of expected format
        // But preserve HTML comments and CDATA sections
        $script = trim($script);

        // Validate that only allowed domains are present in src attributes
        preg_match_all('/src=["\']([^"\']+)["\']/', $script, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                $parsedUrl = parse_url($url);
                if (!$parsedUrl || !isset($parsedUrl['host']) || !in_array($parsedUrl['host'], $allowedDomains)) {
                    return '<!-- Gorgias script blocked: untrusted domain ' . htmlspecialchars($url) . ' -->';
                }

                // Ensure HTTPS only
                if (!isset($parsedUrl['scheme']) || $parsedUrl['scheme'] !== 'https') {
                    return '<!-- Gorgias script blocked: non-HTTPS URL -->';
                }
            }
        }

        // Check for dangerous content while allowing legitimate Gorgias script structure
        // Remove any script content that's not just src loading
        if (preg_match('/<script[^>]*>[^<]*(?:(?!<\/script>).)+[^<]*<\/script>/i', $script)) {
            // If there's actual JavaScript code inside script tags (not just CDATA comments), block it
            if (preg_match('/<script[^>]*>(?:\s*<!--[^>]*?-->\s*<!\[CDATA\[\/\/><!--\s*)?([^<\s]+)(?:\s*\/\/--><!\]\]>\s*)?<\/script>/i', $script, $codeMatches)) {
                if (!empty(trim($codeMatches[1])) && trim($codeMatches[1]) !== '//-->') {
                    return '<!-- Gorgias script blocked: contains executable code -->';
                }
            }
        }

        // Validate overall structure - should only contain HTML comments and script tags
        $cleanedForValidation = preg_replace('/<!--.*?-->/s', '', $script);
        $cleanedForValidation = preg_replace('/<script[^>]*>.*?<\/script>/s', '<script></script>', $cleanedForValidation);
        $cleanedForValidation = trim($cleanedForValidation);

        // After removing comments and script content, should be mostly empty or just script tags
        if (!empty($cleanedForValidation) && !preg_match('/^(\s*<script[^>]*><\/script>\s*)*$/', $cleanedForValidation)) {
            return '<!-- Gorgias script blocked: unexpected content structure -->';
        }

        // Additional check: ensure no dangerous attributes
        if (preg_match('/\son\w+\s*=/i', $script)) {
            return '<!-- Gorgias script blocked: dangerous event attributes -->';
        }

        return $script;
    }
}
