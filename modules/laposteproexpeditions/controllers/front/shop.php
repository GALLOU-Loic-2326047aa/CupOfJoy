<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
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
 * @author    La Poste
 * @copyright 2007-2025 PrestaShop SA / 2024-2025 La Poste
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

if (!defined('_PS_VERSION_')) {
    exit;
}

/*
 * Contains code for the shop rest controller.
 */
use LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Misc\NoticeController;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ApiUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ConfigurationReportUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ConfigurationUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\LoggerUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShopUtil;

/**
 * Shop class.
 *
 * Opens API endpoint to pair.
 */
class LaPosteProExpeditionsShopModuleFrontController extends ModuleFrontController
{
    /**
     * Processes request.
     *
     * @void
     */
    public function postProcess()
    {
        $entityBody = Tools::file_get_contents('php://input');

        $route = Tools::getValue('route'); // Get route

        if ('pair' === $route) {
            AuthUtil::authenticate($entityBody);
            $body = AuthUtil::decryptBody($entityBody);

            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $this->pairingHandler($body);
                        break;

                    default:
                        break;
                }
            }
        } elseif ('update-configuration' === $route) {
            AuthUtil::authenticateAccessKey($entityBody);
            $body = AuthUtil::decryptBody($entityBody);

            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $this->updateConfigurationHandler($body);
                        break;

                    default:
                        break;
                }
            }
        } elseif ('delete-configuration' === $route) {
            AuthUtil::authenticateAccessKey($entityBody);

            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $this->deleteConfigurationHandler();
                        break;

                    default:
                        break;
                }
            }
        } elseif ('get-configuration' === $route) {
            AuthUtil::authenticateAccessKey($entityBody);

            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $this->getConfigurationReportHandler();
                        break;
                    default:
                        break;
                }
            }
        } else {
            LoggerUtil::warn('Incoming shop request failed (400) : unknown route "' . $route . '"');
        }
        return ApiUtil::sendApiResponse(400);
    }

    /**
     * Endpoint callback.
     *
     * @param object|null $body request body
     *
     * @void
     */
    public function pairingHandler($body)
    {
        if (null === $body) {
            LoggerUtil::warn('Incoming pairing request denied (400) : request body is empty');
            return ApiUtil::sendApiResponse(400);
        }

        $backofficeUrl = ConfigurationUtil::getConfigurationUrl();
        $accessKey = null;
        $secretKey = null;
        if (is_object($body) && property_exists($body, 'accessKey')
            && property_exists($body, 'secretKey')) {
            $accessKey = $body->accessKey;
            $secretKey = $body->secretKey;
        }

        if (null !== $accessKey && null !== $secretKey) {
            if (!AuthUtil::isPluginPaired(ShopUtil::$shopGroupId, ShopUtil::$shopId)) { // initial pairing.
                AuthUtil::pairPlugin($accessKey, $secretKey);
                NoticeController::removeNotice(
                    NoticeController::$setupWizard,
                    ShopUtil::$shopGroupId,
                    ShopUtil::$shopId
                );
                NoticeController::addNotice(
                    NoticeController::$pairing,
                    ShopUtil::$shopGroupId,
                    ShopUtil::$shopId,
                    ['result' => 1]
                );
                ApiUtil::sendApiResponse(200, ['pluginConfigurationUrl' => $backofficeUrl]);
            } else {
                LoggerUtil::warn('Incoming pairing request denied (403) : plugin is already paired');
                ApiUtil::sendApiResponse(403);
            }
        } else {
            NoticeController::addNotice(
                NoticeController::$pairing,
                ShopUtil::$shopGroupId,
                ShopUtil::$shopId,
                ['result' => 0]
            );
            LoggerUtil::warn('Incoming pairing request denied (400) : missing access or secret key');
            return ApiUtil::sendApiResponse(400);
        }
    }

    /**
     * Endpoint callback.
     *
     * @void
     */
    public function deleteConfigurationHandler()
    {
        ConfigurationUtil::deleteConfiguration();
        return ApiUtil::sendApiResponse(200);
    }

    /**
     * Endpoint callback.
     *
     * @void
     */
    public function getConfigurationReportHandler()
    {
        $response = ConfigurationReportUtil::getConfigurationReport();
        return ApiUtil::sendApiResponse(200, $response);
    }

    /**
     * Endpoint callback.
     *
     * @param object $body request body
     *
     * @void
     */
    public function updateConfigurationHandler($body)
    {
        if (ConfigurationUtil::parseConfiguration($body)) {
            return ApiUtil::sendApiResponse(200);
        }

        LoggerUtil::warn('Incoming update configuration request failed (400)');
        return ApiUtil::sendApiResponse(400);
    }
}
