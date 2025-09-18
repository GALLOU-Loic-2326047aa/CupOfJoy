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

namespace Bant\BantGorgias\Data;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Address;
use Currency;
use Customer;
use Group;
use Order;
use Prestashop\Prestashop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Domain\Shop\Exception\ShopException;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException;

class Data
{
    const SKIPPED_FIELDS = [
        'passwd',
        'last_passwd_gen',
        'secure_key',
        'reset_password_token',
        'reset_password_validity',
    ];

    const PRICE_FIELDS = [
        'total_discounts',
        'total_discounts_tax_incl',
        'total_discounts_tax_excl',
        'total_paid',
        'total_paid_tax_incl',
        'total_paid_tax_excl',
        'total_paid_real',
        'total_products',
        'total_products_wt',
        'total_shipping',
        'total_shipping_tax_incl',
        'total_shipping_tax_excl',
        'total_wrapping',
        'total_wrapping_tax_incl',
        'total_wrapping_tax_excl',
        'product_price',
        'reduction_amount',
        'reduction_amount_tax_incl',
        'reduction_amount_tax_excl',
        'total_price_tax_incl',
        'total_price_tax_excl',
        'unit_price_tax_incl',
        'unit_price_tax_excl',
        'total_shipping_price_tax_incl',
        'total_shipping_price_tax_excl',
        'purchase_supplier_price',
        'original_product_price',
        'original_wholesale_price',
        'total_refunded_tax_excl',
        'total_refunded_tax_incl',
    ];

    public string $email;
    public array $data;

    public function __construct($email)
    {
        $this->email = $email;
        $this->data = [];
    }

    /**
     * Get all the required information about the customer.
     *
     * @return array
     *
     * @throws LocalizationException
     * @throws \PrestaShopException
     */
    public function getData(): array
    {
        return $this->getCustomers();
    }

    /**
     * Get all customers by their email address.
     *
     * @return array
     *
     * @throws LocalizationException
     * @throws \PrestaShopException
     * @throws ShopException
     */
    private function getCustomers(): array
    {
        $customers = \Customer::getCustomersByEmail($this->email);

        if (!count($customers)) {
            return [];
        }

        $configurationService = new Configuration();

        $adminUrl = $configurationService->get('BANTGORGIAS_ADMIN_URL', '', ShopConstraint::shop(\Context::getContext()->shop->id));

        foreach ($customers as $customer) {
            $cData = $this->getOrders((int) $customer['id_customer']);

            if (!count($cData)) {
                continue;
            }

            foreach ($cData as &$order) {
                if (!isset($order['id_order']) || !$order['id_order']) {
                    continue;
                }
                $currency = new \Currency((int) $order['id_currency']);
                $this->getCustomerData($order, new \Customer((int) $customer['id_customer']));
                $order = $this->getFormattedPrice($order, new \Currency((int) $order['id_currency']));
                $order = $this->removeNotNeededFields($order);
                if ($adminUrl) {
                    $order['admin_url'] = $adminUrl . 'index.php/sell/orders/' . $order['id_order'] . '/view';
                }
                $order['tracking_number'] = $this->getOrderTrackingNumber($order['id_order']);
                $order['products'] = $this->getOrderProducts((int) $order['id_order'], $currency);
                $order['addresses']['billing'] = $this->getOrderAddresses((int) $order['id_address_invoice']);
                $order['addresses']['shipping'] = $this->getOrderAddresses((int) $order['id_address_delivery']);
                $order['currency'] = $currency->iso_code;
            }
            unset($order);

            array_push($this->data, ...$cData);
        }

        return $this->data;
    }

    /**
     * Get the formatted price of the order.
     *
     * @param array $data Order data
     * @param \Currency $currency the currency of the order
     */
    private function getFormattedPrice(array $data, \Currency $currency): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::PRICE_FIELDS, true)) {
                $data['formatted_' . $key] = \Context::getContext()->currentLocale->formatPrice($value, $currency->iso_code);
            }
        }

        return $data;
    }

    /**
     * Get tracking number of order
     *
     * @param $idOrder
     *
     * @return int
     *
     * @throws \PrestaShopException
     */
    private function getOrderTrackingNumber($idOrder): string
    {
        $sql = new \DbQuery();
        $sql->select('tracking_number');
        $sql->from('order_carrier');
        $sql->where('id_order = ' . (int) $idOrder);

        return \Db::getInstance()->getValue($sql);
    }

    /**
     * Get some information from the customer to add to the order.
     *
     * @param array $order
     * @param \Customer $customer
     *
     * @return void
     */
    private function getCustomerData(array &$order, \Customer $customer): void
    {
        $order['name'] = $customer->firstname . ' ' . $customer->lastname;
        $order['email'] = $customer->email;
        $order['is_guest'] = $customer->isGuest();
        $order['group_name'] = $this->getCorrectGroupData($customer->id_default_group, $order['id_lang'], $order['id_shop']);
    }

    /**
     * Get the customer's orders.
     *
     * @param int $customerId the ID of the customer
     *
     * @return array
     */
    private function getOrders(int $customerId): array
    {
        return \Order::getCustomerOrders($customerId, false);
    }

    /**
     * Get the products of an order.
     *
     * @param int $idOrder the ID of the order
     * @param \Currency $currency the currency of the order
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws LocalizationException
     */
    private function getOrderProducts(int $idOrder, \Currency $currency)
    {
        $order = new \Order($idOrder);
        $products = $order->getProductsDetail();

        foreach ($products as &$product) {
            $product = $this->getFormattedPrice($product, $currency);
        }

        return $products;
    }

    /**
     * Get an address from an order by address ID.
     *
     * @param int $idAddress the ID of the address
     *
     * @return array
     */
    private function getOrderAddresses(int $idAddress): array
    {
        $format = \AddressFormat::getFormattedLayoutData(new \Address($idAddress));

        return $format['object'];
    }

    /**
     * @param int $groupId get the customer's group name by group ID
     * @param int $idLang the ID of the language
     * @param int $idShop the ID of the shop
     *
     * @return mixed|string
     */
    public function getCorrectGroupData(int $groupId, int $idLang, int $idShop): mixed
    {
        $groups = \Group::getGroups($idLang, $idShop);

        foreach ($groups as $group) {
            if ((int) $group['id_group'] === $groupId) {
                return $group['name'];
            }
        }

        return '';
    }

    /**
     * Filter out the fields that are not needed.
     *
     * @param array $data the data to be filtered
     *
     * @return array
     */
    private function removeNotNeededFields(array $data): array
    {
        return array_diff_key($data, array_flip(self::SKIPPED_FIELDS));
    }
}
