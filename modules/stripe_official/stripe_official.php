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

use PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Stripe\Account;
use Stripe\Exception\ApiConnectionException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use StripeOfficial\Classes\services\InstallOrderState\CaptureWaitingHandler;
use StripeOfficial\Classes\services\InstallOrderState\OxxoWaitingHandler;
use StripeOfficial\Classes\services\InstallOrderState\PartialRefundHandler;
use StripeOfficial\Classes\services\InstallOrderState\PaymentWaitingHandler;
use StripeOfficial\Classes\services\InstallOrderState\SepaDisputeHandler;
use StripeOfficial\Classes\services\InstallOrderState\SepaWaitingHandler;
use StripeOfficial\Classes\services\MainGetContent\MainGetContentService;
use StripeOfficial\Classes\services\PrestashopTranslationService;
use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}
if (!defined('_PS_USE_MINIFY_JS_')) {
    define('_PS_USE_MINIFY_JS_', true);
}
require_once dirname(__FILE__) . '/vendor/autoload.php';

/**
 * Stripe official PrestaShop module main class extends payment class
 * Please note this module use _202 PrestaShop Classlib Project_ (202 classlib) a library developed by "202 ecommerce"
 * This library provide utils common features as DB installer, internal logger, chain of responsibility design pattern
 *
 * To let module compatible with Prestashop 1.6 please keep this following line commented in PrestaShop 1.6:
 * // use Stripe_officialClasslib\Install\ModuleInstaller;
 * // use Stripe_officialClasslib\Actions\ActionsHandler;
 * // use Stripe_officialClasslib\Extensions\ProcessLogger\ProcessLoggerExtension;
 *
 * Developers use declarative method to define objects, parameters, controllers... needed in this module
 */
class Stripe_official extends PaymentModule
{
    /**
     * Stripe Prestashop configuration
     * use Configuration::get(Stripe_official::CONST_NAME) to return a value
     */
    const KEY = 'STRIPE_KEY';
    const TEST_KEY = 'STRIPE_TEST_KEY';
    const PUBLISHABLE = 'STRIPE_PUBLISHABLE';
    const TEST_PUBLISHABLE = 'STRIPE_TEST_PUBLISHABLE';
    const CAPTURE_WAITING = 'STRIPE_CAPTURE_WAITING';
    const PAYMENT_WAITING = 'STRIPE_PAYMENT_WAITING';
    const SEPA_WAITING = 'STRIPE_SEPA_WAITING';
    const SEPA_DISPUTE = 'STRIPE_SEPA_DISPUTE';
    const OXXO_WAITING = 'STRIPE_OXXO_WAITING';
    const MODE = 'STRIPE_MODE';
    const MINIMUM_AMOUNT_3DS = 'STRIPE_MINIMUM_AMOUNT_3DS';
    const POSTCODE = 'STRIPE_POSTCODE';
    const CARDHOLDERNAME = 'STRIPE_CARDHOLDERNAME';
    const REINSURANCE = 'STRIPE_REINSURANCE';
    const VISA = 'STRIPE_PAYMENT_VISA';
    const MASTERCARD = 'STRIPE_PAYMENT_MASTERCARD';
    const AMERICAN_EXPRESS = 'STRIPE_PAYMENT_AMERICAN_EXPRESS';
    const CB = 'STRIPE_PAYMENT_CB';
    const DINERS_CLUB = 'STRIPE_PAYMENT_DINERS_CLUB';
    const UNION_PAY = 'STRIPE_PAYMENT_UNION_PAY';
    const JCB = 'STRIPE_PAYMENT_JCB';
    const DISCOVERS = 'STRIPE_PAYMENT_DISCOVERS';
    const ENABLE_IDEAL = 'STRIPE_ENABLE_IDEAL';
    const ENABLE_GIROPAY = 'STRIPE_ENABLE_GIROPAY';
    const ENABLE_BANCONTACT = 'STRIPE_ENABLE_BANCONTACT';
    const ENABLE_FPX = 'STRIPE_ENABLE_FPX';
    const ENABLE_EPS = 'STRIPE_ENABLE_EPS';
    const ENABLE_P24 = 'STRIPE_ENABLE_P24';
    const ENABLE_SEPA = 'STRIPE_ENABLE_SEPA';
    const ENABLE_ALIPAY = 'STRIPE_ENABLE_ALIPAY';
    const ENABLE_OXXO = 'STRIPE_ENABLE_OXXO';
    const ENABLE_APPLEPAY_GOOGLEPAY = 'STRIPE_ENABLE_APPLEPAY_GOOGLEPAY';
    const REFUND_ID = 'STRIPE_REFUND_ID';
    const REFUND_MODE = 'STRIPE_REFUND_MODE';
    const REFUND_AMOUNT = 'STRIPE_REFUND_AMOUNT';
    const PARTIAL_REFUND = 'STRIPE_PARTIAL_REFUND';
    const CATCHANDAUTHORIZE = 'STRIPE_CATCHANDAUTHORIZE';
    const CAPTURE_STATUS = 'STRIPE_CAPTURE_STATUS';
    const CAPTURE_EXPIRE = 'STRIPE_CAPTURE_EXPIRE';
    const WEBHOOK_SIGNATURE = 'STRIPE_WEBHOOK_SIGNATURE';
    const WEBHOOK_ID = 'STRIPE_WEBHOOK_ID';
    const ACCOUNT_ID = 'STRIPE_ACCOUNT_ID';
    const ENABLE_PAYMENT_ELEMENTS = 'STRIPE_ENABLE_PAYMENT_ELEMENTS';
    const ENABLE_KLARNA = 'STRIPE_ENABLE_KLARNA';
    const ENABLE_AFTERPAY = 'STRIPE_ENABLE_AFTERPAY';
    const ENABLE_AFFIRM = 'STRIPE_ENABLE_AFFIRM';
    const ENABLE_LINK = 'STRIPE_ENABLE_LINK';
    const THEME = 'STRIPE_THEME';
    const POSITION = 'STRIPE_POSITION';
    const LAYOUT = 'STRIPE_LAYOUT';
    const ENABLE_EXPRESS_CHECKOUT = 'STRIPE_ENABLE_EXPRESS_CHECKOUT';
    const EXPRESS_CHECKOUT_LOCATIONS = 'STRIPE_EXPRESS_CHECKOUT_LOCATIONS';
    const APPLE_PAY_BUTTON_THEME = 'STRIPE_APPLE_PAY_BUTTON_THEME';
    const APPLE_PAY_BUTTON_TYPE = 'STRIPE_APPLE_PAY_BUTTON_TYPE';
    const GOOGLE_PAY_BUTTON_THEME = 'STRIPE_GOOGLE_PAY_BUTTON_THEME';
    const GOOGLE_PAY_BUTTON_TYPE = 'STRIPE_GOOGLE_PAY_BUTTON_TYPE';
    const PAY_PAL_BUTTON_THEME = 'STRIPE_PAY_PAL_BUTTON_THEME';
    const PAY_PAL_BUTTON_TYPE = 'STRIPE_PAY_PAL_BUTTON_TYPE';
    const ORDER_FLOW = 'STRIPE_ORDER_FLOW';
    const ENABLE_SAVE_PAYMENT_METHOD = 'STRIPE_ENABLE_SAVE_PAYMENT_METHOD';

    const TYPE_SAVE_PAYMENT_METHOD_OFF = 'off';
    const TYPE_SAVE_PAYMENT_METHOD_ON_SESSION = 'on_session';
    const TYPE_SAVE_PAYMENT_METHOD_OFF_SESSION = 'off_session';

    // const not used for inputs
    const TYPE_SECRET = 'type_secret';
    const TYPE_PUBLISHABLE = 'type_publishable';
    const MODE_LIVE = 0;
    const MODE_TEST = 1;
    const REFUND_MODE_PARTIAL = 0;
    const REFUND_MODE_FULL = 1;

    // payment method flows
    const PM_FLOW_REDIRECT = 'redirect';
    const PM_FLOW_IFRAME = 'iframe';
    // payment method names
    const PM_PAYMENT_ELEMENTS = 'stripe_payment_elements';
    const PM_CHECKOUT = 'stripe_checkout';
    const PM_EXPRESS_CHECKOUT = 'stripe_express_checkout';
    const PS_USE_MINIFY_JS = '1';
    const STRIPE_API_VERSION = '2022-08-01';
    const PS_MULTISHOP_FEATURE_ACTIVE = '0';
    const MODULE_MAIN_CLASS = 'Modules.Stripeofficial.Stripeofficial';
    const WANTS_TO_USE_CLOUDSYNC = 'STRIPE_WANTS_TO_USE_CLOUDSYNC';

    /**
     * List of objectModel used in this Module
     *
     * @var array
     */
    public $objectModels = [
        'StripePayment',
        'StripePaymentIntent',
        'StripeCapture',
        'StripeCustomer',
        'StripeIdempotencyKey',
        'StripeEvent',
    ];

    /**
     * List of _202 classlib_ extentions
     *
     * @var array
     */
    public $extensions = [
        Stripe_officialClasslib\Extensions\ProcessLogger\ProcessLoggerExtension::class,
    ];

    /**
     * List of ModuleFrontController used in this Module
     * Module::install() register it, after that you can edit it in BO (for rewrite if needed)
     *
     * @var array
     */
    public $controllers = [
        'orderFailure',
        'stripeCards',
    ];

    /**
     * List of hooks needed in this module
     * _202 classlib_ extentions will plugged automatically hooks
     *
     * @var array
     */
    public $hooks = [
        'displayHeader',
        'displayOrderConfirmation',
        'displayBackOfficeHeader',
        'displayAdminOrderTabOrder',
        'displayAdminOrderContentOrder',
        'displayAdminOrderTabLink',
        'displayAdminOrderTabContent',
        'displayAdminCartsView',
        'paymentOptions',
        'displayPaymentEU',
        'actionOrderStatusUpdate',
        'displayProductAdditionalInfo',
        'displayProductActions',
        'displayExpressCheckout',
    ];

    /**
     * @var StripePaymentIntentService
     */
    private $stripePaymentIntentService;

    /**
     * @var ServiceContainer
     */
    private $container;

    // Read the Stripe guide: https://stripe.com/payments/payment-methods-guide
    public static $paymentMethods = [
        self::PM_PAYMENT_ELEMENTS => [
            'name' => self::PM_PAYMENT_ELEMENTS,
            'flow' => self::PM_FLOW_IFRAME,
            'enable' => true,
            'catch_enable' => true,
            'display_in_back_office' => false,
        ],
        self::PM_CHECKOUT => [
            'name' => self::PM_CHECKOUT,
            'flow' => self::PM_FLOW_REDIRECT,
            'enable' => true,
            'catch_enable' => true,
            'display_in_back_office' => false,
        ],
        self::PM_EXPRESS_CHECKOUT => [
            'name' => self::PM_EXPRESS_CHECKOUT,
            'flow' => self::PM_FLOW_IFRAME,
            'enable' => true,
            'catch_enable' => true,
            'display_in_back_office' => false,
        ],
    ];

    public static $allowedPaymentFlows = [
        self::PM_PAYMENT_ELEMENTS,
        self::PM_CHECKOUT,
    ];

    public static $webhook_events = [
        \Stripe\Event::CHARGE_EXPIRED,
        \Stripe\Event::CHARGE_FAILED,
        \Stripe\Event::CHARGE_SUCCEEDED,
        \Stripe\Event::CHARGE_PENDING,
        \Stripe\Event::CHARGE_CAPTURED,
        \Stripe\Event::CHARGE_REFUNDED,
        \Stripe\Event::CHARGE_DISPUTE_CREATED,
        \Stripe\Event::PAYMENT_INTENT_REQUIRES_ACTION,
        \Stripe\Event::PAYMENT_INTENT_SUCCEEDED,
        \Stripe\Event::PAYMENT_INTENT_CANCELED,
    ];

    /* refund */
    public $refund = 0;

    public $errors = [];

    public $warning = [];

    public $success;

    public $display;

    public $meta_title;

    public $button_label = [];

    public $moduleAdminControllers = [];

    /**
     * @var PrestashopTranslationService
     */
    protected $translationService;

    public function __construct()
    {
        $this->name = 'stripe_official';
        $this->tab = 'payments_gateways';
        $this->version = '3.6.5';
        $this->author = 'Stripe';
        $this->bootstrap = true;
        $this->display = 'view';
        $this->module_key = 'bb21cb93bbac29159ef3af00bca52354';
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_,
        ];
        $this->currencies = true;

        parent::__construct();

        $this->translationService = new PrestashopTranslationService($this, self::MODULE_MAIN_CLASS);

        /* curl check */
        if (is_callable('curl_init') === false) {
            $translation = $this->translationService->hasNewTranslationSystem()
                ? $this->translationService->translate('To be able to use this module, please activate cURL (PHP extension).')
                : $this->l('To be able to use this module, please activate cURL (PHP extension).');
        }

        $this->button_label[self::PM_CHECKOUT] = $this->translationService->hasNewTranslationSystem()
            ? $this->translationService->translate('Pay online')
            : $this->l('Pay online');
        $this->button_label[self::PM_PAYMENT_ELEMENTS] = $this->translationService->hasNewTranslationSystem()
            ? $this->translationService->translate('Pay online')
            : $this->l('Pay online');

        $this->meta_title = $this->translationService->hasNewTranslationSystem()
            ? $this->translationService->translate('Stripe')
            : $this->l('Stripe');

        $this->displayName = $this->translationService->hasNewTranslationSystem()
            ? $this->translationService->translate('Stripe payment module')
            : $this->l('Stripe payment module');

        $this->description = $this->translationService->hasNewTranslationSystem()
            ? $this->translationService->translate('Start accepting stripe payments today, directly from your shop!')
            : $this->l('Start accepting stripe payments today, directly from your shop!');

        $this->confirmUninstall = $this->translationService->hasNewTranslationSystem()
            ? $this->translationService->translate('Are you sure you want to uninstall?')
            : $this->l('Are you sure you want to uninstall?');

        require_once realpath(dirname(__FILE__) . '/smarty/plugins') . '/modifier.stripelreplace.php';

        /* Use a specific name to bypass an Order confirmation controller check */
        $bypassControllers = ['orderconfirmation', 'order-confirmation'];
        if (in_array(Tools::getValue('controller'), $bypassControllers)) {
            $this->displayName = $this->translationService->hasNewTranslationSystem()
                ? $this->translationService->translate('Payment by Stripe')
                : $this->l('Payment by Stripe');
        }
        // Do not call Stripe Instance if API keys are not already configured
        if (self::isWellConfigured()) {
            try {
                Stripe::setApiKey($this->getSecretKey());
                $this->setStripeAppInformation();
            } catch (ApiConnectionException $e) {
                StripeProcessLogger::logError('Fail to set API Key. Stripe SDK return error: ' . $e, 'stripe_official');
            }
        }

        if ($this->container === null) {
            $this->container = new ServiceContainer(
                $this->name,
                $this->getLocalPath()
            );
        }

        $errorFlags = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];

        register_shutdown_function(function () use ($errorFlags) {
            $error = error_get_last();
            if (
                $error !== null
                && in_array($error['type'], $errorFlags)
                && isset($error['file'])
                && strpos($error['file'], 'stripe_official') !== false
            ) {
                StripeProcessLogger::logError('Fatal error: ' . json_encode($error), 'GeneralErrors');
            }
        });

        $oldErrHandler = set_error_handler(null);
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($errorFlags, $oldErrHandler) {
            if (in_array($errno, $errorFlags) || strpos($errfile, 'stripe_official') !== false) {
                StripeProcessLogger::logError(
                    'Uncaught error ' . $errno . ': ' . $errstr . ' at: ' . $errfile . ':' . $errline,
                    'GeneralErrors',
                    $this->context->cart ? $this->context->cart->id : ''
                );
            }
            if ($oldErrHandler) {
                $oldErrHandler($errno, $errstr, $errfile, $errline);
            }
        });

        $oldHandler = set_exception_handler(null);
        set_exception_handler(function (Throwable $exception) use ($oldHandler) {
            if (strpos($exception->getFile(), 'stripe_official') !== false) {
                StripeProcessLogger::logError(
                    'Uncaught exception: ' . $exception->getMessage() . ' at: ' . $exception->getFile() . ':' . $exception->getLine() . ' Trace: ' . $exception->getTraceAsString(),
                    'GeneralErrors',
                    $this->context->cart ? $this->context->cart->id : ''
                );
            }
            // Call the previous handler if it exists
            if ($oldHandler) {
                $oldHandler($exception);
            }
        });
    }

    /**
     * Check if configuration is completed. If not, disabled frontend features.
     */
    public static function isWellConfigured()
    {
        $shopGroupId = Stripe_official::getShopGroupIdContext();
        $shopId = Stripe_official::getShopIdContext();
        $publishableKey = self::getPublishableKey($shopGroupId, $shopId);
        $secretKey = self::getSecretKey($shopGroupId, $shopId);

        if ($publishableKey && $secretKey) {
            return true;
        }

        return false;
    }

    private function createLockTable()
    {
        StripePdoLockService::createTable();
    }

    private function dropLockTable()
    {
        $sql = 'DROP TABLE IF EXISTS `' . StripePdoLockService::TABLE_NAME . '`;';

        return Db::getInstance()->execute($sql);
    }

    /**
     * install module depends _202 classlib_ to install hooks, objects models tables, admin controller, ...
     */
    public function install()
    {
        /* CloudSync */
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();
        if (!$moduleManager->isInstalled('ps_eventbus')) {
            $moduleManager->install('ps_eventbus');
        } elseif (!$moduleManager->isEnabled('ps_eventbus')) {
            $moduleManager->enable('ps_eventbus');
            $moduleManager->upgrade('ps_eventbus');
        } else {
            $moduleManager->upgrade('ps_eventbus');
        }

        try {
            if (!parent::install()) {
                return false;
            }

            $this->getService('stripe_official.ps_accounts_installer')->install();

            $installer = new Stripe_officialClasslib\Install\ModuleInstaller($this);
            $installer->installObjectModel('StripeAccountDetails');
            if (!$installer->install()) {
                StripeProcessLogger::logError('our installer error', 3);

                return false;
            }

            $sql = 'SHOW KEYS FROM `' . _DB_PREFIX_ . "stripe_event` WHERE Key_name = 'ix_id_payment_intentstatus'";
            if (!Db::getInstance()->executeS($sql)) {
                $sql = 'SELECT MAX(id_stripe_event) AS id_stripe_event FROM `' . _DB_PREFIX_ . 'stripe_event` GROUP BY `id_payment_intent`, `status`';
                $duplicateRows = Db::getInstance()->executeS($sql);

                $idList = array_column($duplicateRows, 'id_stripe_event');

                if (!empty($idList)) {
                    $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'stripe_event` WHERE id_stripe_event NOT IN (' . implode(',', $idList) . ');';
                    Db::getInstance()->execute($sql);
                }

                $sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'stripe_event` ADD UNIQUE `ix_id_payment_intentstatus` (`id_payment_intent`, `status`);';
                Db::getInstance()->execute($sql);
            }
            $shopGroupId = Stripe_official::getShopGroupIdContext();
            $shopId = Stripe_official::getShopIdContext();

            $this->createLockTable();

            // preset default values
            if (!Configuration::updateValue(self::MODE, 1, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::REFUND_MODE, 1, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::MINIMUM_AMOUNT_3DS, 50, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_IDEAL, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_GIROPAY, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_BANCONTACT, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_FPX, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_EPS, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_P24, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_SEPA, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_KLARNA, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_AFTERPAY, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_AFFIRM, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_LINK, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_ALIPAY, 0, false, $shopGroupId, $shopId)
                || !Configuration::updateValue(self::ENABLE_OXXO, 0, false, $shopGroupId, $shopId)) {
                return false;
            }

            if (!$this->installOrderState()) {
                return false;
            }

            /*
             * Clear both Smarty and Symfony cache.
             */
            Tools::clearAllCache();

            return true;
        } catch (Exception $e) {
            StripeProcessLogger::logError('Stripe_official - install exception ' . $e->getMessage() . ' - ' . $e->getTraceAsString());

            return false;
        }
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        $id_product_attribute = $productQuantity = null;
        if (isset($params['product'])) {
            $id_product = $params['product']['id_product'];
            $id_product_attribute = $params['product']['id_product_attribute'];
            $productQuantity = PrestaShop\PrestaShop\Adapter\Entity\StockAvailable::getQuantityAvailableByProduct($id_product, $id_product_attribute);
            $outOfStockFlag = StockAvailable::outOfStock($id_product, Context::getContext()->shop->id);
            $params = [
                'product' => [
                    'id_product' => $id_product,
                    'out_of_stock' => (int) $outOfStockFlag,
                ],
            ];

            $productLazyArray = null;
            try {
                $productLazyArray = StripeOfficial\Classes\factories\StripeProductLazyArrayFactory::createProductLazyArrayFromProductId($id_product, $this->context);
            } catch (Throwable $e) {
                StripeProcessLogger::logError('Cannot get ProductLazyArray: ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'stripe_official');
            }

            Hook::exec('displayProductActions', !empty($productLazyArray) ? ['product' => $productLazyArray] : $params);
        }

        $this->context->smarty->assign([
            'id_product_attribute' => $id_product_attribute,
            'product_quantity' => $productQuantity,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product-page.tpl');
    }

    /**
     * Retrieve the service
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public function getService($serviceName)
    {
        return $this->container->getService($serviceName);
    }

    /**
     * install module depends _202 classlib_ to remove hooks, admin controller
     * please note objects models tables are NOT removed to keep payments data
     */
    public function uninstall()
    {
        $installer = new Stripe_officialClasslib\Install\ModuleInstaller($this);

        $result = parent::uninstall();
        $result &= $installer->uninstallModuleAdminControllers();
        $result &= $installer->uninstallConfiguration();

        $this->dropLockTable();

        /*
         * Clear both Smarty and Symfony cache.
         */
        Tools::clearAllCache();

        return $result
            && Configuration::deleteByName(self::MODE)
            && Configuration::deleteByName(self::REFUND_MODE)
            && Configuration::deleteByName(self::ENABLE_IDEAL)
            && Configuration::deleteByName(self::ENABLE_GIROPAY)
            && Configuration::deleteByName(self::ENABLE_BANCONTACT)
            && Configuration::deleteByName(self::ENABLE_FPX)
            && Configuration::deleteByName(self::ENABLE_EPS)
            && Configuration::deleteByName(self::ENABLE_P24)
            && Configuration::deleteByName(self::ENABLE_SEPA)
            && Configuration::deleteByName(self::ENABLE_KLARNA)
            && Configuration::deleteByName(self::ENABLE_AFTERPAY)
            && Configuration::deleteByName(self::ENABLE_AFFIRM)
            && Configuration::deleteByName(self::ENABLE_LINK)
            && Configuration::deleteByName(self::ENABLE_ALIPAY)
            && Configuration::deleteByName(self::ENABLE_OXXO);
    }

    /**
     * Reset module and clear cache
     */
    public function reset()
    {
        $installer = new Stripe_officialClasslib\Install\ModuleInstaller($this);

        $result = parent::uninstall();
        $result &= $installer->clearHookUsed();
        $result &= $installer->uninstallModuleAdminControllers();
        $result &= $installer->installAdminControllers();

        /*
         * Clear both Smarty and Symfony cache.
         */
        Tools::clearAllCache();

        return $result;
    }

    /**
     * Disable module and clear cache
     */
    public function disabled()
    {
        $module = Module::getInstanceByName('stripe_official');
        $module->disable(false);

        /*
         * Clear both Smarty and Symfony cache.
         */
        Tools::clearAllCache();
    }

    /**
     * Enable module and clear cache
     */
    public function enabled()
    {
        $module = Module::getInstanceByName('stripe_official');
        $module->enable(false);

        /*
         * Clear both Smarty and Symfony cache.
         */
        Tools::clearAllCache();
    }

    /**
     * Create order state
     *
     * @return bool
     */
    public function installOrderState()
    {
        $handlers = [
            new CaptureWaitingHandler($this),
            new SepaWaitingHandler($this),
            new SepaDisputeHandler($this),
            new OxxoWaitingHandler($this),
            new PaymentWaitingHandler($this),
            new PartialRefundHandler($this),
        ];
        foreach ($handlers as $handler) {
            $handler->handle();
        }

        return true;
    }

    /**
     * description: render main content
     * Important The order in which the actions are performed in this method is important
     */
    public function getContent()
    {
        $contentService = new MainGetContentService($this);

        return $contentService->getContent();
    }

    public static function isZeroDecimalCurrency($currency)
    {
        // @see: https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
        $zeroDecimalCurrencies = [
            'BIF',
            'CLP',
            'DJF',
            'GNF',
            'JPY',
            'KMF',
            'KRW',
            'MGA',
            'PYG',
            'RWF',
            'UGX',
            'VND',
            'VUV',
            'XAF',
            'XOF',
            'XPF',
        ];

        return in_array(Tools::strtoupper($currency), $zeroDecimalCurrencies);
    }

    /**
     * Get a list of files contained in directory
     *
     * @param string $dir Target directory path
     * @param string $regex Apply regex
     * @param false $onlyFilename Get only filename
     * @param array $results Results search
     *
     * @return array
     */
    private static function getDirContentFiles($dir, $regex = '/.*/', $onlyFilename = false, &$results = [])
    {
        $files = scandir($dir);

        foreach ($files as $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path) && preg_match($regex, $value)) {
                $results[] = $onlyFilename ? $value : $path;
            } elseif (is_dir($path) && $value != '.' && $value != '..') {
                self::getDirContentFiles($path, $regex, $onlyFilename, $results);
            }
        }

        return $results;
    }

    /**
     * clean cache for upgrade to prevent issue during module upgrade
     *
     * @return void
     */
    public function cleanModuleCache()
    {
        $path = _PS_MODULE_DIR_ . 'stripe_official/views/templates';
        $regPattern = '/.*\.tpl/';
        $templates = self::getDirContentFiles($path, $regPattern, true);

        foreach ($templates as $tpl) {
            $this->_clearCache($tpl);
        }
    }

    /**
     * get webhook url of stripe_official module
     *
     * @return string
     */
    public static function getWebhookUrl()
    {
        $context = Context::getContext();
        $locale = $context->language->iso_code;

        $url = $context->link->getModuleLink(
            'stripe_official',
            'webhook',
            [],
            true,
            null,
            Configuration::get('PS_SHOP_DEFAULT')
        );

        return str_replace('/' . $locale, '', $url);
    }

    /**
     * get current LangId according to activate multishop feature
     *
     * @return int|null
     */
    public static function getLangIdContext()
    {
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && Shop::getContext() === Shop::CONTEXT_ALL) {
            return Configuration::get('PS_LANG_DEFAULT', null, 1, 1);
        }

        return Configuration::get('PS_LANG_DEFAULT');
    }

    /**
     * get current ShopId according to activate multishop feature
     *
     * @return int|null
     */
    public static function getShopIdContext()
    {
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            return Context::getContext()->shop->id;
        }

        return Configuration::get('PS_SHOP_DEFAULT');
    }

    /**
     * get current ShopGroupId according to activate multishop feature
     *
     * @return int|null
     */
    public static function getShopGroupIdContext()
    {
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            return Context::getContext()->shop->id_shop_group;
        }

        return Configuration::get('PS_SHOP_DEFAULT');
    }

    /**
     * get Secret Key according MODE staging or live
     *
     * @param null $id_shop Optional, if set, get the secret key of the specified shop
     *
     * @return string
     */
    public static function getSecretKey($id_shop_group = null, $id_shop = null)
    {
        $shopGroupId = $id_shop_group ?: Stripe_official::getShopGroupIdContext();
        $shopId = $id_shop ?: Stripe_official::getShopIdContext();
        $mode = (int) Configuration::get(self::MODE, null, $shopGroupId, $shopId);
        $secretKeyConstant = self::getApiKeyConstantByModeAndType($mode, self::TYPE_SECRET);

        return Configuration::get($secretKeyConstant, null, $shopGroupId, $shopId);
    }

    /**
     * get Publishable Key according MODE staging or live
     *
     * @return string
     */
    public static function getPublishableKey($id_shop_group = null, $id_shop = null)
    {
        $shopGroupId = $id_shop_group ?: Stripe_official::getShopGroupIdContext();
        $shopId = $id_shop ?: Stripe_official::getShopIdContext();
        $mode = (int) Configuration::get(self::MODE, null, $shopGroupId, $shopId);
        $publishableKeyConstant = self::getApiKeyConstantByModeAndType($mode, self::TYPE_PUBLISHABLE);

        return Configuration::get($publishableKeyConstant, null, $shopGroupId, $shopId);
    }

    public function checkApiConnection($secretKey = null)
    {
        if (!$secretKey) {
            $secretKey = $this->getSecretKey();
        }

        try {
            Stripe::setApiKey($secretKey);

            return Account::retrieve();
        } catch (Exception $e) {
            StripeProcessLogger::logError('Retrieve Account Issue' . $e->getMessage(), 'stripe_official');

            return false;
        }
    }

    public function updateConfigurationKey($oldKey, $newKey)
    {
        if (Configuration::hasKey($oldKey)) {
            $set = '';

            if ($oldKey == '_PS_STRIPE_secure' && Configuration::get($oldKey) == '0') {
                $set = ',`value` = 2';
            }

            $sql = 'UPDATE `' . _DB_PREFIX_ . 'configuration`
                    SET `name`="' . pSQL($newKey) . '"' . $set . '
                    WHERE `name`="' . pSQL($oldKey) . '"';

            return Db::getInstance()->execute($sql);
        }
    }

    public function captureFunds($amount, $id_payment_intent)
    {
        Stripe::setApiKey($this->getSecretKey());

        try {
            $intent = PaymentIntent::retrieve($id_payment_intent);
            if ($intent->amount_capturable === 0) {
                return false;
            }
            $intent->capture(['amount_to_capture' => $amount]);

            return true;
        } catch (ApiConnectionException $e) {
            StripeProcessLogger::logError('Fail to capture amount. Stripe SDK return error: ' . $e, 'stripe_official');

            return false;
        }
    }

    /**
     * Set JS var in backoffice
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        try {
            if (
                Tools::getIsset('controller')
                && in_array(Tools::getValue('controller'), ['AdminModules', 'AdminModulesManage'])
                && Tools::getValue('configure') == $this->name
            ) {
                $router = PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance()->get('router');
                if ($router) {
                    $csrfToken = StripeOfficial\Classes\services\PrestashopAdminTokenService::getToken('SkipCloudSyncDependencies');
                    Media::addJsDef([
                        'skip_cloudsync_dependencies_url' => $router->generate('stripe_official_skip_cloudsync_dependencies'),
                        'skip_cloudsync_token' => $csrfToken,
                        'get_cloudsync_dependencies_url' => $router->generate('stripe_official_get_cloudsync_dependencies'),
                        'cloudsync_clear_cache_url' => $router->generate('stripe_official_cloudsync_clear_cache'),
                    ]);

                    $this->context->controller->addJS($this->_path . 'views/js/back.js');
                }
            }
        } catch (Throwable $e) {
            StripeProcessLogger::logInfo('[ERROR in hookDisplayBackOfficeHeader]: ' . $e->getMessage(), 'stripe_official');
        }
    }

    /**
     * Add a tab to controle intents on a cart details admin page
     */
    public function hookDisplayAdminCartsView($params)
    {
        $stripePayment = new StripePayment();
        $paymentInformations = $stripePayment->getStripePaymentByCart($params['cart']->id);

        if (empty($paymentInformations->getIdPaymentIntent())) {
            return;
        }

        $paymentInformations->state = $paymentInformations->state ? 'TEST' : 'LIVE';
        $paymentInformations->url_dashboard = $stripePayment->getDashboardUrl();

        $this->context->smarty->assign([
            'paymentInformations' => $paymentInformations,
            'use_new_ps_translation' => $this->translationService->hasNewTranslationSystem(),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/admin_cart.tpl');
    }

    /**
     * Add a tab to controle intents on an order details admin page (tab header)
     *
     * @return html|bool
     */
    public function hookDisplayAdminOrderTabOrder($params)
    {
        if (!$this->getSecretKey() || !$this->getPublishableKey()) {
            return false;
        }

        if (isset($params['id_order'])) {
            $order = new Order($params['id_order']);
        } else {
            $order = $params['order'];
        }

        if ($order->module != 'stripe_official') {
            return false;
        }

        return $this->display(__FILE__, 'views/templates/hook/admin_tab_order.tpl');
    }

    public function hookDisplayAdminOrderTabLink($params)
    {
        return $this->hookDisplayAdminOrderTabOrder($params);
    }

    /**
     * Add a tab to controle intents on an order details admin page (tab content)
     *
     * @return html|bool
     */
    public function hookDisplayAdminOrderContentOrder($params)
    {
        if (!$this->getSecretKey() || !$this->getPublishableKey()) {
            return false;
        }

        if (isset($params['id_order'])) {
            $order = new Order($params['id_order']);
        } else {
            $order = $params['order'];
        }

        $stripePayment = new StripePayment();
        $stripePayment->getStripePaymentByCart($order->id_cart);

        $stripeCapture = new StripeCapture();
        $stripeCapture->getByIdPaymentIntent($stripePayment->getIdPaymentIntent());

        $dispute = false;
        if (!empty($stripePayment->getIdStripe())) {
            $stripeDispute = new StripeDispute();
            $dispute = $stripeDispute->orderHasDispute($stripePayment->getIdStripe(), $order->id_shop);
        }

        $riskLevel = $riskScore = $stripeCustomerID = $paymentMethod = $intent = null;
        try {
            if ($stripePayment->getIdPaymentIntent()) {
                $intent = PaymentIntent::retrieve(
                    $stripePayment->getIdPaymentIntent()
                );
            }

            if ($intent && isset($intent->charges->data[0])) {
                $riskLevel = $intent->charges->data[0]->outcome->risk_level;
                $riskScore = $intent->charges->data[0]->outcome->risk_score;
                $stripeCustomerID = $intent->charges->data[0]->customer;
                $paymentMethod = $intent->charges->data[0]->payment_method_details->type;
            }
        } catch (Exception $e) {
            StripeProcessLogger::logError('Hook Display Admin Order Content Order' . $e->getMessage(), 'stripe_official');

            return false;
        }

        $stripePartialRefunded = false;
        $stripeRefundedAmount = 0;
        $currency = new Currency($order->id_currency);
        $orderCurrencyIsoCode = isset($currency->iso_code) ? $currency->iso_code : null;
        if ((int) $order->current_state === (int) Configuration::get(self::PARTIAL_REFUND)) {
            $stripePartialRefunded = true;
            $stripeRefundedAmount = $orderCurrencyIsoCode ? $stripePayment->getRefund() . ' ' . $orderCurrencyIsoCode : $stripePayment->getRefund();
        }

        $this->context->smarty->assign([
            'stripe_charge' => $stripePayment->getIdStripe(),
            'stripe_paymentIntent' => $stripePayment->getIdPaymentIntent(),
            'stripe_dashboardUrl' => $stripePayment->getDashboardUrl(),
            'stripe_paymentType' => $stripePayment->getType(),
            'stripe_paymentMethod' => $paymentMethod,
            'stripe_dateCatch' => $stripeCapture->getDateCatch(),
            'stripe_dateAuthorize' => $stripeCapture->getDateAuthorize(),
            'stripe_expired' => $stripeCapture->getExpired(),
            'stripe_dispute' => $dispute,
            'stripe_voucher_expire' => $stripePayment->getVoucherExpire(),
            'stripe_voucher_validate' => $stripePayment->getVoucherValidate(),
            'stripe_riskLevel' => $riskLevel,
            'stripe_riskScore' => $riskScore,
            'stripe_customerID' => $stripeCustomerID,
            'stripe_partialRefunded' => $stripePartialRefunded,
            'stripe_refundedAmount' => $stripeRefundedAmount,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/admin_content_order.tpl');
    }

    public function hookDisplayAdminOrderTabContent($params)
    {
        return $this->hookDisplayAdminOrderContentOrder($params);
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $order = new Order($params['id_order']);

        if ($order->module == 'stripe_official'
            && !empty($order->getHistory($this->context->language->id, Configuration::get(self::CAPTURE_WAITING)))
            && in_array($params['newOrderStatus']->id, explode(',', Configuration::get(self::CAPTURE_STATUS)))) {
            $stripePayment = new StripePayment();

            try {
                $stripePaymentDatas = $stripePayment->getStripePaymentByCart($order->id_cart);
                $amount = self::isZeroDecimalCurrency($stripePayment->currency) ? $order->total_paid : $order->total_paid * 100;

                if (!$this->captureFunds($amount, $stripePaymentDatas->id_payment_intent)) {
                    return false;
                }

                $stripeCapture = new StripeCapture();
                $stripeCapture->getByIdPaymentIntent($stripePaymentDatas->id_payment_intent);
                $stripeCapture->date_authorize = date('Y-m-d H:i:s');
                $stripeCapture->save();
            } catch (\Stripe\Exception\UnexpectedValueException $e) {
                StripeProcessLogger::logError('Hook Action Order Status Update - UnexpectedValueException' . $e->getMessage(), 'stripe_official');

                return false;
            } catch (PrestaShopException $e) {
                StripeProcessLogger::logError('Hook Action Order Status Update - PrestaShopException' . $e->getMessage(), 'stripe_official');

                return false;
            }
        }

        return true;
    }

    /*
        Display Express Checkout Element on the Product Page
    */
    public function hookDisplayProductActions()
    {
        // fetch product information from Smarty variables
        $priceAmount = isset($this->context->smarty->tpl_vars['product']->value->price_amount) ? $this->context->smarty->tpl_vars['product']->value->price_amount : null;
        $productId = isset($this->context->smarty->tpl_vars['product']->value->id_product) ? $this->context->smarty->tpl_vars['product']->value->id_product : null;

        // fall back to fetching product from the URL and load the object if fetching it from smarty fails
        if (!$priceAmount || !$productId) {
            $productId = Tools::getValue('id_product');

            if ($productId) {
                $product = new Product($productId, true, $this->context->language->id);

                if (Validate::isLoadedObject($product)) {
                    $priceAmount = $product->getPrice(true);
                    $productId = $product->id;
                }
            }
        }

        if (isset($priceAmount) && isset($productId)) {
            $shopId = Context::getContext()->shop->id;
            $outOfStockFlag = StockAvailable::outOfStock($productId, $shopId);

            // if out of stock and not allowed to order -> hide express checkout
            if (StockAvailable::getQuantityAvailableByProduct($productId) <= 0 && (int) $outOfStockFlag !== 1) {
                return null;
            }

            $isoCode = strtolower($this->context->currency->iso_code);
            $stripeExpressAmount = round($priceAmount, 2);
            $stripeExpressAmount = Stripe_official::isZeroDecimalCurrency($isoCode) ?
                (int) $stripeExpressAmount :
                (int) number_format($stripeExpressAmount * 100, 0, '', '');
            $customerModel = CustomerModel::getFromContext($this->context);
            $address = new Address(Address::getFirstCustomerAddressId($this->context->customer->id));
            $phone = $address->phone;
            $cartId = $this->context->cart->id ?: null;

            Media::addJsDef([
                'stripe_product_out_of_stock' => (int) $outOfStockFlag,
                'stripe_express_amount' => $stripeExpressAmount,
                'stripe_express_currency_iso' => $isoCode,
                'stripe_express_cart_id' => $cartId,
                'stripe_express_customer_model' => $customerModel,
                'stripe_express_phone' => $phone,
                'stripe_express_product_id' => $productId,
                'stripe_express_return_url' => $this->context->link->getModuleLink(
                    'stripe_official',
                    'orderConfirmationReturn',
                    ['cartId' => $this->context->cart->id],
                    true
                ),
            ]);

            return $this->context->smarty->fetch(
                'module:' . $this->name . '/views/templates/front/express_checkout.tpl'
            );
        }

        // if product details are missing
        return null;
    }

    /*
        Display Express Checkout Element on the Cart Page
    */
    public function hookDisplayExpressCheckout()
    {
        if ($this->context->cart === null) {
            return;
        }
        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);
        $stripeExpressAmount = $cart->getOrderTotal();
        $stripeExpressAmount = round($stripeExpressAmount, 2);
        $stripeExpressAmount = Stripe_official::isZeroDecimalCurrency($currency->iso_code) ?
            (int) $stripeExpressAmount :
            (int) number_format($stripeExpressAmount * 100, 0, '', '');
        $isoCode = strtolower($this->context->currency->iso_code);
        $customerModel = CustomerModel::getFromContext($this->context);
        $address = new Address(Address::getFirstCustomerAddressId($this->context->customer->id));
        $phone = $address->phone;
        $cartId = $this->context->cart->id ?: null;
        $cartProducts = $cart->getProducts();
        $productsOutOfStock = Stripe_official::anyCartProductOutOfStock($cartProducts);

        Media::addJsDef([
            'stripe_express_amount' => $stripeExpressAmount,
            'stripe_express_currency_iso' => $isoCode,
            'stripe_express_cart_id' => $cartId,
            'stripe_express_customer_model' => $customerModel,
            'stripe_express_phone' => $phone,
            'stripe_out_of_stock' => $productsOutOfStock,
            'stripe_express_return_url' => $this->context->link->getModuleLink(
                'stripe_official',
                'orderConfirmationReturn',
                ['cartId' => $this->context->cart->id],
                true
            ),
        ]);

        return $this->context->smarty->fetch(
            'module:' . $this->name . '/views/templates/front/express_checkout.tpl'
        );
    }

    /**
     * Load JS on the front office order page
     */
    public function hookDisplayHeader()
    {
        (new StripeOfficial\Classes\services\StripeDisplayHeaderService($this))->setHeaders();
    }

    public function hookDisplayPaymentEU($params)
    {
        return [];
    }

    /**
     * Hook Stripe Payment
     */
    public function hookPaymentOptions($params)
    {
        if (!self::isWellConfigured() || !$this->active) {
            return [];
        }

        $stripeAccount = $this->checkApiConnection();
        if (!$stripeAccount instanceof Account) {
            return [];
        }

        $shopGroupId = Stripe_official::getShopGroupIdContext();
        $shopId = Stripe_official::getShopIdContext();

        $paymentMethod = self::$paymentMethods[self::PM_PAYMENT_ELEMENTS];
        if (!Configuration::get(self::ENABLE_PAYMENT_ELEMENTS, null, $shopGroupId, $shopId)) {
            $paymentMethod = self::$paymentMethods[self::PM_CHECKOUT];
        }

        $paymentOption = new PaymentOption();
        $paymentOption
            ->setModuleName($this->name)
            // ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/example.png'))
            ->setCallToActionText($this->button_label[$paymentMethod['name']]);

        // Display additional information for redirect and receiver based payment methods
        if ($paymentMethod['flow'] === self::PM_FLOW_REDIRECT) {
            $paymentOption->setAdditionalInformation(
                $this->context->smarty->fetch(
                    'module:' . $this->name . '/views/templates/front/payment_info_redirect.tpl'
                )
            );
        }

        // Payment methods with embedded form fields
        if ($paymentMethod['flow'] === self::PM_FLOW_IFRAME) {
            $this->context->smarty->assign([
                'use_new_ps_translation' => $this->translationService->hasNewTranslationSystem(),
            ]);
            $paymentOption->setForm(
                $this->context->smarty->fetch(
                    'module:' . $this->name . '/views/templates/front/payment_form_card.tpl'
                )
            );
        }
        $paymentOption->setAction($this->context->link->getModuleLink(
            $this->name,
            'handleOrderAction',
            [],
            true
        ));

        return [$paymentOption];
    }

    /**
     * Hook Order Confirmation
     */
    public function hookDisplayOrderConfirmation($params)
    {
        $order = $params['order'];
        $prestashop_version = '1.7';

        if (!self::isWellConfigured() || !$this->active || $order->module != $this->name) {
            return;
        }

        $stripePayment = new StripePayment();
        $stripePayment->getStripePaymentByCart($order->id_cart);

        $this->context->smarty->assign([
            'stripe_order_reference' => pSQL($order->reference),
            'prestashop_version' => $prestashop_version,
            'stripePayment' => $stripePayment,
            'use_new_ps_translation' => $this->translationService->hasNewTranslationSystem(),
        ]);

        return $this->display(__FILE__, 'views/templates/front/order-confirmation.tpl');
    }

    public static function getApiKeyConstantByModeAndType($mode, $type)
    {
        switch ($mode) {
            case self::MODE_LIVE:
                if ($type === self::TYPE_PUBLISHABLE) {
                    return self::PUBLISHABLE;
                }
                if ($type === self::TYPE_SECRET) {
                    return self::KEY;
                }
                break;
            case self::MODE_TEST:
                if ($type === self::TYPE_PUBLISHABLE) {
                    return self::TEST_PUBLISHABLE;
                }
                if ($type === self::TYPE_SECRET) {
                    return self::TEST_KEY;
                }
                break;
        }

        return '';
    }

    public function setStripeAppInformation()
    {
        $version = $this->version . '_' . _PS_VERSION_ . '_' . phpversion();
        Stripe::setAppInfo(
            'StripePrestashop',
            $version,
            'https://addons.prestashop.com/en/payment-card-wallet/24922-stripe-official.html',
            'pp_partner_EX2Z2idAZw7OWr'
        );
    }

    public function anyCartProductOutOfStock($cartProducts)
    {
        $shopId = Context::getContext()->shop->id;

        foreach ($cartProducts as $product) {
            $productId = (int) $product['id_product'];

            $outOfStockFlag = StockAvailable::outOfStock($productId, $shopId);

            // if product is out of stock and not allowed to be ordered -> return true
            if ((int) $product['quantity_available'] <= 0 && (int) $outOfStockFlag !== 1) {
                return true;
            }
        }

        return false;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getTranslationService()
    {
        return $this->translationService;
    }

    public function isUsingNewTranslationSystem()
    {
        return $this->translationService->hasNewTranslationSystem();
    }

    public function isCloudSyncReady()
    {
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();

        return $moduleManager->isInstalled('ps_eventbus') && $moduleManager->isInstalled('ps_accounts') && $moduleManager->isInstalled('ps_mbo');
    }
}
