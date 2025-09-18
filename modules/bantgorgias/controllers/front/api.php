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

use Bant\BantGorgias\Api\Api;
use JetBrains\PhpStorm\NoReturn;
use Prestashop\Prestashop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;

class BantGorgiasApiModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $ajax;

    public function __construct()
    {
        parent::__construct();

        $this->display_header = false;
        $this->display_header_javascript = false;
        $this->display_footer = false;
    }

    /**
     * Process the connection request to Gorgias
     * based on the request parameters
     *
     * @throws PrestaShopException|PrestaShop\PrestaShop\Core\Domain\Shop\Exception\ShopException
     * @throws Exception
     */
    #[NoReturn]
    public function display(): void
    {
        $this->ajax = true;

        $this->checkRequestType();

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['gorgiasDomain']) || !$data['gorgiasDomain']
            || !isset($data['shopUuid']) || !$data['shopUuid']
            || !isset($data['shopDomain']) || !$data['shopDomain']
        ) {
            $this->printResponse(false, 400, 'Missing information. Gorgias account or Prestashop shop information missing');
        }

        $configurationService = new Configuration();
        $moduleManager = ModuleManagerBuilder::getInstance()->build();

        $gorgiasDomain = $data['gorgiasDomain'] ?? '';
        $shopUuid = $data['shopUuid'] ?? '';
        $shopDomain = $data['shopDomain'] ?? '';
        $orderState = (int) $data['orderState'] ?? 0;
        $accountsData = $data['accountsData'] ?? [];
        $refundOrderState = (int) $data['refundOrderState'] ?? 0;
        $chat = $data['chat'] ?? '';
        $authUser = $data['authUser'] ?? '';
        $authPass = $data['authPass'] ?? '';

        $configurationService->set('BANTGORGIAS_ORDERSTATE_CANCELLED', $orderState, ShopConstraint::shop($this->context->shop->id));
        $configurationService->set('BANTGORGIAS_ORDERSTATE_REFUNDED', $refundOrderState, ShopConstraint::shop($this->context->shop->id));
        $configurationService->set('BANTGORGIAS_DOMAIN', $gorgiasDomain, ShopConstraint::shop($this->context->shop->id));
        $configurationService->set('BANTGORGIAS_CHAT', $chat, ShopConstraint::shop($this->context->shop->id), ['html' => true]);
        $configurationService->set('BANTGORGIAS_AUTHUSER', $authUser, ShopConstraint::shop($this->context->shop->id));
        $configurationService->set('BANTGORGIAS_AUTHPASS', $authPass, ShopConstraint::shop($this->context->shop->id));

        $api = new Api($shopUuid, $shopDomain, 'api/prestashop/connect');

        $response = $api->post([
            'shop_id' => $shopUuid,
            'shop_url' => Tools::getShopProtocol() . $shopDomain,
            'gorgias_domain' => $gorgiasDomain,
            'accounts_data' => $accountsData,
            'module_status' => $moduleManager->isEnabled('bantgorgias'),
            'http_username' => $authUser,
            'http_password' => $authPass,
        ]);

        $response = preg_replace('/^[^\[]*(?=\[)/', '', $response);
        $response = json_decode($response, true);

        if (!isset($response['install_url'])
            || !isset($response['api_key'])
            || !$response['install_url']
            || !$response['api_key']
        ) {
            $this->printResponse(false, 400, 'Missing information. Error connecting to Gorgias');
        }

        $configurationService->set('BANTGORGIAS_API_KEY', $response['api_key']);

        $this->printResponse(true, 200, $response['install_url']);
    }

    /**
     * Check the request type to make sure it is a POST request
     *
     * @throws PrestaShopException
     */
    private function checkRequestType(): void
    {
        if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') !== 'POST') {
            $this->printResponse(false, 405, 'The GET method is not supported');
        }
    }

    /**
     * Prints a response to the client
     *
     * @param bool $status Status of the response which can be true or false
     * @param int $statusCode HTTP status code
     * @param string $message Message of the response
     * @param string|array|object|null $trace Trace of the error
     *
     * @throws PrestaShopException
     */
    #[NoReturn]
    private function printResponse(bool $status, int $statusCode, string $message, string|array|object|null $trace = null): void
    {
        http_response_code($statusCode);
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        $this->ajaxRender(json_encode([
            'status' => $status,
            'message' => $message,
            'trace' => $trace ?: null,
        ]));
        exit;
    }
}
