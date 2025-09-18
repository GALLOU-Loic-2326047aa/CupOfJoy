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

use JetBrains\PhpStorm\NoReturn;
use Prestashop\Prestashop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\CommandBus;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\IssueReturnProductCommand;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;

class BantGorgiasOrderModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public $ajax;

    private Configuration $configurationService;

    private CommandBus\CommandBusInterface $commandBus;

    public function __construct()
    {
        parent::__construct();
        $this->display_header = false;
        $this->display_header_javascript = false;
        $this->display_footer = false;
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
     * Process the order cancellation/refund and update its status
     * based on the action and the order ID parameters
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws PrestaShop\PrestaShop\Core\Domain\Order\Exception\InvalidCancelProductException
     * @throws PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException
     * @throws PrestaShop\PrestaShop\Core\Domain\Shop\Exception\ShopException
     */
    #[NoReturn]
    public function display(): void
    {
        global $kernel;
        $this->ajax = true;

        $this->checkRequestType();

        if ($kernel === null) {
            $kernel = new FrontKernel('prod', false);
            $kernel->boot();
        }

        $this->configurationService = new Configuration();

        $this->commandBus = $kernel->getContainer()->get('prestashop.core.command_bus');

        $orderId = (int) Tools::getValue('id_order', 0);

        if (!$orderId) {
            $this->printResponse(false, 400, 'Missing Order ID');
        }

        $order = new Order($orderId);

        if (!Validate::isLoadedObject($order)) {
            $this->printResponse(false, 400, 'Order is invalid or does not exist');
        }

        $action = trim(Tools::getValue('action', ''));

        if (!$action) {
            $this->printResponse(false, 400, 'Missing Action');
        }

        if ((int) $this->configurationService->get('PS_ORDER_RETURN') <= 0) {
            $this->printResponse(false, 400, 'Returning products are disabled');
        }

        switch ($action) {
            case 'cancel':
                $cancelOrderState = (int) $this->configurationService->get('BANTGORGIAS_ORDERSTATE_CANCELLED', 0, ShopConstraint::shop($this->context->shop->id));
                if (!$cancelOrderState) {
                    $this->printResponse(false, 400, 'Missing cancel order state');
                }
                $this->processCancel($order);
                // no break
            case 'refund':
                $refundOrderState = (int) $this->configurationService->get('BANTGORGIAS_ORDERSTATE_REFUNDED', 0, ShopConstraint::shop($this->context->shop->id));
                if (!$refundOrderState) {
                    $this->printResponse(false, 400, 'Missing refund order state');
                }
                $stock = (int) Tools::getValue('stock', 0);

                if ($order->hasBeenDelivered()) {
                    $this->processReturn($order, (bool) $stock);
                } else {
                    $this->processRefund($order, (bool) $stock);
                }

                // no break
            default:
                $this->printResponse(false, 400, 'Invalid Action');
        }
    }

    /**
     * Process the order cancellation and return and update its status
     *
     * @param Order $order Order object
     * @param bool $stock If the stock should be restocked
     *
     * @throws PrestaShopException
     * @throws PrestaShop\PrestaShop\Core\Domain\Shop\Exception\ShopException
     * @throws PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException
     * @throws PrestaShop\PrestaShop\Core\Domain\Order\Exception\InvalidCancelProductException
     */
    private function processReturn(Order $order, bool $stock = false): void
    {
        // Get all order details (products) for full return
        $orderDetails = $order->getOrderDetailList();
        $returnedProductQuantities = [];

        // Prepare all products for return with their full quantities
        foreach ($orderDetails as $orderDetail) {
            $returnedProductQuantities[$orderDetail['id_order_detail']] = [
                'quantity' => (int) $orderDetail['product_quantity'],
            ];
        }
        // Create the command object with all parameters for full return
        $command = new IssueReturnProductCommand(
            $order->id,
            $returnedProductQuantities, // All products with full quantities
            $stock,// Restock products by default
            true, // Refund shipping by default for full return
            true,// Generate credit slip by default
            false,  // Don't generate voucher by default
            0, // No voucher amount by default
        );

        try {
            $this->commandBus->handle($command);
        } catch (Throwable $e) {
            $this->printResponse(false, 400, 'An error occurred while processing the return: ' . get_class($e) . ' - ' . $e->getMessage(), $e->getTraceAsString());
        }
        $refundOrderState = (int) $this->configurationService->get('BANTGORGIAS_ORDERSTATE_REFUNDED', 0, ShopConstraint::shop($this->context->shop->id));

        $order->setCurrentState($refundOrderState);

        $this->printResponse(true, 200, 'Order returned successfully');
    }

    /**
     * Process the order cancellation and refund and update its status
     *
     * @param Order $order Order object
     * @param bool $stock If the stock should be restocked
     *
     * @throws PrestaShopException
     * @throws PrestaShop\PrestaShop\Core\Domain\Shop\Exception\ShopException
     * @throws PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException
     * @throws PrestaShop\PrestaShop\Core\Domain\Order\Exception\InvalidCancelProductException
     */
    #[NoReturn]
    private function processRefund(Order $order, bool $stock = false): void
    {
        // Get all order details (products) for full return
        $orderDetails = $order->getOrderDetailList();
        $returnedProductQuantities = [];

        // Prepare all products for return with their full quantities
        foreach ($orderDetails as $orderDetail) {
            $returnedProductQuantities[$orderDetail['id_order_detail']] = [
                'quantity' => (int) $orderDetail['product_quantity'],
            ];
        }
        // Create the command object with all parameters for full return
        $command = new IssueReturnProductCommand(
            $order->id,
            $returnedProductQuantities, // All products with full quantities
            $stock,// Restock products by default
            true, // Refund shipping by default for full return
            true,// Generate credit slip by default
            false,  // Don't generate voucher by default
            0, // No voucher amount by default
        );

        $command = new PrestaShop\PrestaShop\Core\Domain\Order\Command\IssueStandardRefundCommand(
            $order->id,
            $returnedProductQuantities,
            true,
            true,
            false,
            0
        );

        try {
            $this->commandBus->handle($command);
        } catch (Throwable $e) {
            $this->printResponse(false, 400, 'An error occurred while processing the refund: ' . get_class($e) . ' - ' . $e->getMessage(), $e->getTraceAsString());
        }
        $refundOrderState = (int) $this->configurationService->get('BANTGORGIAS_ORDERSTATE_REFUNDED', 0, ShopConstraint::shop($this->context->shop->id));

        $order->setCurrentState($refundOrderState);

        $this->printResponse(true, 200, 'Order refunded successfully');
    }

    /**
     * Process the order cancellation and update its status
     *
     * @param Order $order
     *
     * @throws PrestaShopException
     * @throws PrestaShop\PrestaShop\Core\Domain\Shop\Exception\ShopException
     */
    #[NoReturn]
    private function processCancel(Order $order): void
    {
        if ($order->getCurrentOrderState() === (int) $this->configurationService->get('BANTGORGIAS_ORDERSTATE_CANCELLED', 0, ShopConstraint::shop($this->context->shop->id))) {
            $this->printResponse(false, 400, 'Order is already cancelled');
        }

        if ($order->setCurrentState((int) $this->configurationService->get('BANTGORGIAS_ORDERSTATE_CANCELLED', 0, ShopConstraint::shop($this->context->shop->id))) === false) {
            $this->printResponse(false, 500, 'An error occurred while cancelling the order', 'Order state could not be set to cancelled. Please check the order state configuration.');
        }

        $this->printResponse(true, 200, 'Order cancelled successfully');
    }

    /**
     * Check if there is an Authorization header set
     *
     * Checks both `$_SERVER['Authorization']` and `$_SERVER['HTTP_AUTHORIZATION']`
     * and returns the value if it exists, otherwise an empty string.
     *
     * @return string
     */
    private function checkForBasicAuth(): string
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
