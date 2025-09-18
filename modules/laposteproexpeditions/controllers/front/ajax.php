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
 * Contains code for the front ajax controller class.
 */
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ApiUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\CartStorageUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\EncodeUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\FrontendUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ParcelPointUtil;
use LaPoste\LaPosteProExpeditionsPrestashop\Util\ShippingMethodUtil;

/**
 * Front ajax controller class.
 *
 * @class       LaPosteProExpeditionsAjaxModuleFrontController
 */
class LaPosteProExpeditionsAjaxModuleFrontController extends ModuleFrontController
{
    private function getPostedParcelPoint()
    {
        return ParcelPointUtil::createParcelPoint(
            Tools::getValue('network'),
            Tools::getValue('code'),
            Tools::getValue('name'),
            Tools::getValue('address'),
            Tools::getValue('zipcode'),
            Tools::getValue('city'),
            Tools::getValue('country'),
            @json_decode(Tools::getValue('openingHours')),
            Tools::getValue('distance')
        );
    }

    /**
     * Ajax front controller.
     *
     * @void
     */
    public function initContent()
    {
        if (!$this->isTokenValid()) {
            ApiUtil::sendAjaxResponse(403);
        }

        $this->ajax = true;
        parent::initContent();
        $route = Tools::getValue('route'); // Get route
        if ('getSelectedCarrierText' === $route) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $selectedCarrierId = (int) Tools::getValue('carrier');
                        $cartId = (int) Tools::getValue('cartId');
                        $this->getSelectedCarrierTextHandler($cartId, $selectedCarrierId);
                        break;

                    default:
                        break;
                }
            }
        }

        if ('getPoints' === $route) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $selectedCarrierId = (int) Tools::getValue('carrier');
                        $cartId = (int) Tools::getValue('cartId');
                        $this->getPointsHandler($cartId, $selectedCarrierId);
                        break;

                    default:
                        break;
                }
            }
        }

        if ('setPoint' === $route) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $selectedCarrierId = (int) Tools::getValue('carrier');
                        $cartId = (int) Tools::getValue('cartId');
                        $this->setPointHandler($cartId, $selectedCarrierId, $this->getPostedParcelPoint());
                        break;

                    default:
                        break;
                }
            }
        }

        if ('getMapUrl' === $route) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $this->getMapUrl();
                        break;

                    default:
                        break;
                }
            }
        }

        ApiUtil::sendAjaxResponse(400);
    }

    /**
     * Returns parcel point' one lined address
     *
     * @param mixed $parcelPoint parcel point
     *
     * @return string
     */
    private function getParcelPointOneLineAddress($parcelPoint)
    {
        $ziptown = implode(' ', array_filter([$parcelPoint->zipcode, $parcelPoint->city]));
        $address = implode(', ', array_filter([$parcelPoint->address, $ziptown]));

        return $address;
    }

    /**
     * Returns selected carrier text.
     *
     * @param int $cartId cart id
     * @param int $selectedCarrierId selected carrier id
     *
     * @void
     */
    public function getSelectedCarrierTextHandler($cartId, $selectedCarrierId)
    {
        $text = '';
        if (ShippingMethodUtil::hasSelectedParcelPointNetworks($selectedCarrierId)) {
            $storageParcelPoints = CartStorageUtil::get($cartId, 'lpParcelPoints');
            $pointsResponse = EncodeUtil::decode($storageParcelPoints);
            if (false !== $pointsResponse) {
                $instance = LaPosteProExpeditions::getInstance();

                $message = $instance->l('Your parcel point:');
                $parcelPoint = ParcelPointUtil::getChosenPoint($cartId, $selectedCarrierId);
                if (null === $parcelPoint) {
                    $message = $instance->l('Closest parcel point:');
                    $parcelPoint = FrontendUtil::getCartClosestPoint($cartId, $selectedCarrierId);
                    if (null === $parcelPoint) {
                        ApiUtil::sendAjaxResponse(404);
                    }
                }

                $distanceFromSearchLocation = '';
                if (null !== $parcelPoint->distanceFromSearchLocation) {
                    $distance = round($parcelPoint->distanceFromSearchLocation / 1000, 1);
                    $distanceFromSearchLocation = ' (' . sprintf($instance->l('%s km away'), $distance) . ')';
                }

                $text .= '<br/><span class="lp-parcel-client">'
                    . '<b>' . $message . '</b>'
                    . '<span class="lp-parcel-name">'
                        . $parcelPoint->name . ' </span></br>'
                    . '<span class="lp-parcel-address">'
                        . $this->getParcelPointOneLineAddress($parcelPoint)
                        . $distanceFromSearchLocation . '</span>'
                    . '</span>'
                    . '<br/><span class="lp-select-parcel">'
                        . $instance->l('Choose another') . '</span>';
            }
        }
        ApiUtil::sendAjaxResponse(200, ['text' => $text]);
    }

    /**
     * Returns selected carrier text.
     *
     * @param int $cartId cart id
     * @param int $selectedCarrierId selected carrier id
     *
     * @void
     */
    public function getPointsHandler($cartId, $selectedCarrierId)
    {
        $pointsResponse = EncodeUtil::decode(
            CartStorageUtil::get($cartId, 'lpParcelPoints')
        );
        $networks = ShippingMethodUtil::getSelectedParcelPointNetworks($selectedCarrierId);
        if (false !== $pointsResponse && property_exists($pointsResponse, 'nearbyParcelPoints')
            && is_array($pointsResponse->nearbyParcelPoints) && count($pointsResponse->nearbyParcelPoints) > 0) {
            $points = [];
            foreach ($pointsResponse->nearbyParcelPoints as $parcelPoint) {
                if (property_exists($parcelPoint, 'parcelPoint')
                    && property_exists($parcelPoint->parcelPoint, 'network')
                    && in_array($parcelPoint->parcelPoint->network, $networks)) {
                    $points[] = $parcelPoint;
                }
            }
            if (!empty($points)) {
                $response = new stdClass();
                $response->searchLocation = $pointsResponse->searchLocation;
                $response->nearbyParcelPoints = $points;
                ApiUtil::sendAjaxResponse(200, $response);
            }
        }

        ApiUtil::sendAjaxResponse(404);
    }

    /**
     * Returns selected carrier text.
     *
     * @param int|null $cartId cart id
     * @param int|null $selectedCarrierId selected carrier id
     * @param mixed|null $parcelPoint
     *
     * @void
     */
    public function setPointHandler($cartId, $selectedCarrierId, $parcelPoint)
    {
        if (null === $selectedCarrierId || null === $cartId || null === $parcelPoint) {
            ApiUtil::sendAjaxResponse(400);
        }
        ParcelPointUtil::setChosenPoint((int) $cartId, $selectedCarrierId, $parcelPoint);

        ApiUtil::sendAjaxResponse(200);
    }

    /**
     * Returns map url.
     *
     * @void
     */
    public function getMapUrl()
    {
        $mapUrl = FrontendUtil::getMapUrl();

        if ($mapUrl === null) {
            ApiUtil::sendAjaxResponse(404);
        }

        ApiUtil::sendAjaxResponse(200, ['mapUrl' => $mapUrl]);
    }
}
