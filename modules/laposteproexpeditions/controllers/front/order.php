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
 * Contains code for the order rest controller.
 */
use LaPoste\LaPosteProExpeditionsPrestashop\Controllers\Misc\NoticeController;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ApiUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\AuthUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ConfigurationUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\LoggerUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\MiscUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\OrderUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ParcelPointUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ProductUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShippingMethodUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShopUtil;

/**
 * Order reset controller.
 *
 * Opens API endpoint to sync orders.
 */
class LaPosteProExpeditionsOrderModuleFrontController extends ModuleFrontController
{
    /**
     * Processes request.
     *
     * @void
     */
    public function postProcess()
    {
        $entityBody = Tools::file_get_contents('php://input');

        AuthUtil::authenticateAccessKey($entityBody);

        $route = Tools::getValue('route'); // Get route

        if ('order' === $route) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $this->retrieveOrdersHandler();
                        break;

                    default:
                        break;
                }
            }
        } elseif ('shipped' === $route || 'delivered' === $route || 'prepared' === $route) {
            $orderId = Tools::getValue('orderId');
            $body = AuthUtil::decryptBody($entityBody);
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $this->trackingEventHandler($orderId, $route, $body);
                        break;

                    default:
                        break;
                }
            }
        }

        LoggerUtil::warn('Incoming tracking event request failed (400)');
        return ApiUtil::sendApiResponse(400);
    }

    /**
     * Endpoint callback.
     *
     * @void
     */
    public function retrieveOrdersHandler()
    {
        $response = $this->getOrders();
        LoggerUtil::info('Retrieve orders request returned ' . count($response) . ' order(s)');
        return ApiUtil::sendApiResponse(200, ['orders' => $response]);
    }

    /**
     * Get Prestashop orders.
     *
     * @return array $result
     */
    public function getOrders()
    {
        $orders = OrderUtil::getOrders();
        $result = [];

        foreach ($orders as $order) {
            if (null !== MiscUtil::notEmptyOrNull($order, 'id_order')) {
                $orderId = (int) MiscUtil::notEmptyOrNull($order, 'id_order');
            } else {
                continue;
            }

            $phone = null === MiscUtil::notEmptyOrNull($order, 'phone_mobile') ?
                MiscUtil::notEmptyOrNull($order, 'phone')
                : MiscUtil::notEmptyOrNull($order, 'phone_mobile');
            $recipient = [
                'firstname' => MiscUtil::notEmptyOrNull($order, 'firstname'),
                'lastname' => MiscUtil::notEmptyOrNull($order, 'lastname'),
                'company' => MiscUtil::notEmptyOrNull($order, 'company'),
                'addressLine1' => MiscUtil::notEmptyOrNull($order, 'address1'),
                'addressLine2' => MiscUtil::notEmptyOrNull($order, 'address2'),
                'city' => MiscUtil::notEmptyOrNull($order, 'city'),
                'state' => MiscUtil::notEmptyOrNull($order, 'state_iso'),
                'postcode' => MiscUtil::notEmptyOrNull($order, 'postcode'),
                'country' => MiscUtil::notEmptyOrNull($order, 'country_iso'),
                'phone' => $phone,
                'email' => MiscUtil::notEmptyOrNull($order, 'email'),
            ];
            $items = OrderUtil::getItemsFromOrder($orderId);
            $products = [];
            foreach ($items as $item) {
                $product = [];
                $product['weight'] = 0. !== (float) $item['product_weight'] ? (float) $item['product_weight'] : null;
                $product['quantity'] = (int) $item['product_quantity'];
                $product['price'] = (float) $item['product_price'];
                $description = ProductUtil::getProductDescriptionMultilingual((int) $item['product_id']);
                $product['description'] = $description;
                $products[] = $product;
            }

            $parcelPointData = null;
            $parcelPoint = ParcelPointUtil::getOrderParcelPoint($orderId);
            if (null !== $parcelPoint) {
                $parcelPointData = [
                    'code' => $parcelPoint->code,
                    'network' => $parcelPoint->network,
                ];
            }

            $multilingualStatus = OrderUtil::getStatusMultilingual($orderId);
            $multilingualShippingMethod = [];
            $shippingMethodName = MiscUtil::notEmptyOrNull($order, 'shippingMethod');
            foreach (Language::getLanguages(true) as $lang) {
                $multilingualShippingMethod[
                    Tools::strtolower(str_replace('-', '_', $lang['language_code']))
                ] = $shippingMethodName;
            }

            $result[] = [
                'internalReference' => $orderId,
                'reference' => MiscUtil::notEmptyOrNull($order, 'reference'),
                'status' => [
                    'key' => OrderUtil::getStatusId($orderId),
                    'translations' => $multilingualStatus,
                ],
                'shippingMethod' => [
                    'key' => OrderUtil::getCarrierReference($orderId),
                    'translations' => $multilingualShippingMethod,
                ],
                'shippingAmount' => MiscUtil::toFloatOrNull(MiscUtil::notEmptyOrNull($order, 'shippingAmount')),
                'creationDate' => MiscUtil::dateW3Cformat(MiscUtil::notEmptyOrNull($order, 'creationDate')),
                'orderAmount' => MiscUtil::toFloatOrNull(MiscUtil::notEmptyOrNull($order, 'orderAmount')),
                'recipient' => $recipient,
                'products' => $products,
                'parcelPoint' => $parcelPointData,
            ];
        }

        return $result;
    }

    /**
     * Insert shipping number into an order.
     *
     * @param Order $order
     */
    private function insertShippingNumber($order)
    {
        $carrierId = OrderUtil::getCarrierId($order->id);
        if (null !== $carrierId) {
            $url = ShippingMethodUtil::getCarrierTrackingUrl($carrierId);
            $urlPattern = ConfigurationUtil::getTrackingUrlPattern();
            if (null !== $url && str_replace('@', '%s', $url) === $urlPattern) {
                if (method_exists($order, 'setWsShippingNumber')) {
                    $order->setWsShippingNumber((int) $order->id);
                } else {
                    Db::getInstance()->update(
                        'orders',
                        ['shipping_number' => (int) $order->id],
                        'id_order = ' . (int) $order->id
                    );

                    Db::getInstance()->update(
                        'order_carrier',
                        ['tracking_number' => (int) $order->id],
                        'id_order = ' . (int) $order->id
                    );
                }
            }
        }
    }

    /**
     * Endpoint callback.
     *
     * @param int $orderId order id
     * @param 'shipped'|'delivered'|'prepared' $route tracking event
     * @param object $body request body
     *
     * @void
     */
    public function trackingEventHandler($orderId, $route, $body)
    {
        $instance = LaPosteProExpeditions::getInstance();
        if (!is_object($body) || !property_exists($body, 'accessKey')
            || $body->accessKey !== AuthUtil::getAccessKey(ShopUtil::$shopGroupId, ShopUtil::$shopId)) {
            LoggerUtil::warn('Incoming tracking event request failed (403)');
            return ApiUtil::sendApiResponse(403);
        }

        if (!is_numeric($orderId)) {
            LoggerUtil::warn('Incoming tracking event request failed (400)');
            return ApiUtil::sendApiResponse(400);
        }

        $langId = $instance->getContext()->language->id;
        $orderStatuses = OrderUtil::getOrderStatuses($langId);

        if ('prepared' === $route) {
            $orderPrepared = ConfigurationUtil::getAsInt('LP_ORDER_PREPARED');
            if (null !== $orderPrepared) {
                $isValidOrderPrepared = false;
                foreach ($orderStatuses as $status) {
                    if ($status['id_order_state'] === $orderPrepared) {
                        $isValidOrderPrepared = true;
                    }
                }

                if (false === $isValidOrderPrepared) {
                    ConfigurationUtil::set('LP_ORDER_PREPARED', null);
                    NoticeController::addNotice(
                        NoticeController::$custom,
                        ShopUtil::$shopGroupId,
                        ShopUtil::$shopId,
                        [
                            'status' => 'warning',
                            // phpcs:ignore Generic.Files.LineLength
                            'message' => sprintf($instance->l('%s : there\'s been a change in your order status list, we\'ve adapted your tracking event configuration. Please check that everything is in order.'), LaPosteProExpeditions::getInstance()->displayName),
                        ]
                    );
                } else {
                    $order = new Order((int) $orderId);
                    $order->setCurrentState($orderPrepared);
                    $this->insertShippingNumber($order);
                }
            }
        }

        if ('shipped' === $route) {
            $orderShipped = ConfigurationUtil::getAsInt('LP_ORDER_SHIPPED');
            if (null !== $orderShipped) {
                $isValidOrderShipped = false;
                foreach ($orderStatuses as $status) {
                    if ($status['id_order_state'] === $orderShipped) {
                        $isValidOrderShipped = true;
                    }
                }

                if (false === $isValidOrderShipped) {
                    ConfigurationUtil::set('LP_ORDER_SHIPPED', null);
                    NoticeController::addNotice(
                        NoticeController::$custom,
                        ShopUtil::$shopGroupId,
                        ShopUtil::$shopId,
                        [
                            'status' => 'warning',
                            // phpcs:ignore Generic.Files.LineLength
                            'message' => sprintf($instance->l('%s : there\'s been a change in your order status list, we\'ve adapted your tracking event configuration. Please check that everything is in order.'), LaPosteProExpeditions::getInstance()->displayName),
                        ]
                    );
                } else {
                    $order = new Order((int) $orderId);
                    $order->setCurrentState($orderShipped);
                    $this->insertShippingNumber($order);
                }
            }
        }

        if ('delivered' === $route) {
            $orderDelivered = ConfigurationUtil::getAsInt('LP_ORDER_DELIVERED');
            if (null !== $orderDelivered) {
                $isValidOrderDelivered = false;
                foreach ($orderStatuses as $status) {
                    if ($status['id_order_state'] === $orderDelivered) {
                        $isValidOrderDelivered = true;
                    }
                }

                if (false === $isValidOrderDelivered) {
                    ConfigurationUtil::set('LP_ORDER_DELIVERED', null);
                    NoticeController::addNotice(
                        NoticeController::$custom,
                        ShopUtil::$shopGroupId,
                        ShopUtil::$shopId,
                        [
                            'status' => 'warning',
                            // phpcs:ignore Generic.Files.LineLength
                            'message' => sprintf($instance->l('%s : there\'s been a change in your order status list, we\'ve adapted your tracking event configuration. Please check that everything is in order.'), LaPosteProExpeditions::getInstance()->displayName),
                        ]
                    );
                } else {
                    $order = new Order((int) $orderId);
                    $order->setCurrentState($orderDelivered);
                }
            }
        }

        return ApiUtil::sendApiResponse(200);
    }
}
