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
namespace LaPoste\LaPosteProExpeditionsPrestashop\Util;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Shipping api util class.
 *
 * Helper to manage api request to an external shipping service.
 */
class ShippingApiUtil
{
    /**
     * Execute a request and Log the http response if this is an error.
     *
     * @param string $method request http method
     * @param string $path request path
     * @param array $body request body
     *
     * @return mixed request response
     */
    private static function request($method, $path, $body = null)
    {
        $accessKey = AuthUtil::getAccessKey(ShopUtil::$shopGroupId, ShopUtil::$shopId);
        $secretKey = AuthUtil::getSecretKey(ShopUtil::$shopGroupId, ShopUtil::$shopId);
        $headers = [
            'Authorization' => base64_encode($accessKey . ':' . $secretKey),
            'Content-type' => 'application/json; charset=UTF-8',
        ];

        $response = WebserviceUtil::request($method, $path, $headers, $body);

        if (!self::isSuccessResponse($response)) {
            LoggerUtil::warn('Request to shipping api failed : ' . "\n"
                . '[Request]  ' . $method . ':' . $path . "\n"
                . 'body: ' . json_encode($body) . "\n"
                . 'headers: ' . json_encode($headers) . "\n"
                . 'available libraries: ' . json_encode(WebserviceUtil::getAvailableLibraries()) . "\n"
                . '[Response] ' . json_encode($response));
        }

        return $response;
    }

    /**
     * Extract a body or null from an http response
     *
     * @param array $response request response
     *
     * @return mixed|null extracted content as a json object
     */
    private static function getContent($response)
    {
        $result = null;

        if (is_array($response) && $response['content'] && !WebserviceUtil::isErrorResponse($response)) {
            $result = json_decode($response['content']);
        }

        return $result;
    }

    /**
     * Check is the response is a success http response (2XX)
     *
     * @param array $response request response
     *
     * @return bool the response is a success http response
     */
    private static function isSuccessResponse($response)
    {
        $status = null;

        if (is_array($response) && !WebserviceUtil::isErrorResponse($response)) {
            $status = $response['status'];
        }

        return null !== $status && $status >= 200 && $status < 300;
    }

    /**
     * Request a shipping order from it's woocommerce id
     *
     * @param int $reference shipping order reference
     *
     * @return mixed array
     */
    public static function getOrder($reference)
    {
        $response = self::request('GET', 'https://api.expeditions-pro.laposte.fr/v2/shop-order/' . $reference);

        return self::getContent($response);
    }

    /**
     * Retrieve a list of parcel points.
     *
     * @param array $address parcel point address
     * @param array $networks wanted parcel point networks
     *
     * @return mixed array list of parcel points per network
     */
    public static function getParcelPoints($address, $networks)
    {
        $parcel_points = null;
        $body = [
            'networks' => $networks,
            'address' => [
                'zipCode' => $address['zipCode'],
                'country' => $address['country'],
            ],
        ];
        if (isset($address['street'])) {
            $body['address']['street'] = $address['street'];
        }

        if (isset($address['city'])) {
            $body['address']['city'] = $address['city'];
        }

        $response = self::request('POST', 'https://api.expeditions-pro.laposte.fr/v2/parcel-point', $body);

        if (self::isSuccessResponse($response)) {
            $parcel_points = self::getContent($response);
        }

        return $parcel_points;
    }

    /**
     * Retrieve a new parcel points map token.
     *
     * @param string $path map token unique endpoint
     *
     * @return mixed json response
     */
    public static function getMapToken($path)
    {
        $map_token = null;

        $response = self::request('POST', $path);

        $response = self::getContent($response);

        if (null !== $response && property_exists($response, 'accessToken')) {
            $map_token = $response->accessToken;
        }

        return $map_token;
    }
}
