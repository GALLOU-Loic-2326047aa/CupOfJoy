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

use Bant\BantGorgias\Data\Data;
use JetBrains\PhpStorm\NoReturn;
use Prestashop\Prestashop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;

class BantGorgiasGetModuleFrontController extends ModuleFrontController
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
     * Process the request and return all orders for the email address provided
     *
     * @throws PrestaShopException
     * @throws PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     * @throws PrestaShop\PrestaShop\Core\Domain\Shop\Exception\ShopException
     */
    public function display()
    {
        $this->ajax = true;

        $this->checkRequestType();

        $configurationService = new Configuration();

        $apiKey = $this->checkForBasicAuth();

        if (mb_strpos($apiKey, 'Bearer') !== false) {
            $apiKey = str_replace('Bearer ', '', $apiKey);
            $savedApiKey = $configurationService->get('BANTGORGIAS_API_KEY', '', ShopConstraint::shop($this->context->shop->id));

            if (!$apiKey || $apiKey !== $savedApiKey) {
                $this->printResponse(false, 401, 'Missing or wrong authentication');
            }
        }

        $email = trim(Tools::getValue('email', ''));

        if (!$email) {
            $this->printResponse(false, 400, 'Missing information. No email address provided');
        }

        $gorgias = new Data($email);

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        $this->ajaxRender(trim(json_encode($gorgias->getData()), "\xEF\xBB\xBF"));
        exit;
    }

    private function checkForBasicAuth()
    {
        if (isset($_SERVER['Authorization']) && $_SERVER['Authorization']) {
            return $_SERVER['Authorization'];
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION']) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        return '';
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
