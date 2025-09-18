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
 * Webservice util class.
 *
 * Helper to external api requests
 */
class WebserviceUtil
{
    /**
     * Execute a request using the first available library
     *
     * @param string $method request path
     * @param string $path request parameters
     * @param array $headers request parameters
     * @param array $body request parameters
     *
     * @return mixed request response
     */
    public static function request($method, $path, $headers = [], $body = null)
    {
        $response = self::getErrorResponse('No request library available');

        if ($response['status'] === 0 && self::isFopenAvailable()) {
            $response = self::doFopenRequest($method, $path, $headers, $body);
        }

        if ($response['status'] === 0 && self::isCurlAvailable()) {
            $response = self::doCurlRequest($method, $path, $headers, $body);
        }

        return $response;
    }

    /**
     * Return the list of available libraries (curl and / or fopen)
     *
     * @return array available libraries
     */
    public static function getAvailableLibraries()
    {
        return [
            'fopen' => self::isFopenAvailable(),
            'curl' => self::isCurlAvailable(),
        ];
    }

    /**
     * check if fopen is available
     *
     * @return bool
     */
    private static function isFopenAvailable()
    {
        $ini = ini_get('allow_url_fopen');

        return '' !== $ini && false !== $ini && '0' !== $ini && 0 !== $ini;
    }

    /**
     * check if cURL is available
     *
     * @return bool
     */
    private static function isCurlAvailable()
    {
        return extension_loaded('curl');
    }

    /**
     * Execute a request using fopen
     *
     * @param string $method request path
     * @param string $path request parameters
     * @param array $headers request parameters
     * @param array $body request parameters
     *
     * @return mixed request response
     */
    private static function doFopenRequest($method, $path, $headers, $body)
    {
        $header = '';
        foreach ($headers as $key => $value) {
            $header .= $key . ': ' . $value . "\r\n";
        }

        $params = [
            'http' => [
                'method' => $method,
                'header' => $header,
                'content' => $method !== 'GET' ? json_encode($body) : null,
            ],
        ];

        $context = stream_context_create($params);

        $stream = @fopen($path, 'r', false, $context);

        if (false === $stream) {
            $response = self::getErrorResponse('Failed to open stream using fopen');
        } else {
            $status = self::getFopenResponseStatus($stream);
            $content = stream_get_contents($stream);
            fclose($stream);

            $response = self::getSuccessResponse($content, $status);
        }

        return $response;
    }

    /**
     * Execute a request using cURL
     *
     * @param string $method request path
     * @param string $path request parameters
     * @param array $headers request parameters
     * @param array $body request parameters
     *
     * @return mixed request response
     */
    private static function doCurlRequest($method, $path, $headers, $body)
    {
        $curl = curl_init();

        $params = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $path,
        ];

        if (strpos($path, 'https') !== 0) {
            $params[CURLOPT_SSL_VERIFYPEER] = false;
            $params[CURLOPT_SSL_VERIFYHOST] = 0;
        } else {
            $params[CURLOPT_SSL_VERIFYPEER] = true;
            $params[CURLOPT_SSL_VERIFYHOST] = 2;
        }

        $headerArray = [];
        foreach ($headers as $key => $value) {
            $headerArray[] = $key . ': ' . $value;
        }
        $params[CURLOPT_HTTPHEADER] = $headerArray;

        if ($method !== 'GET') {
            $params[CURLOPT_CUSTOMREQUEST] = $method;
            if (!empty($body)) {
                $params[CURLOPT_POSTFIELDS] = json_encode($body);
            }
        }

        curl_setopt_array($curl, $params);
        $result = curl_exec($curl);

        if (false === $result) {
            $response = self::getErrorResponse('Failed to open stream using curl');
        } else {
            $status = self::getCurlResponseStatus($curl);
            $response = self::getSuccessResponse($result, $status);
        }

        return $response;
    }

    /**
     * Return an error response
     *
     * @param string $message error message
     * @param int $status http status
     *
     * @return array error response
     */
    private static function getErrorResponse($message, $status = 0)
    {
        return [
            'message' => $message,
            'content' => null,
            'status' => $status,
            'error' => true,
        ];
    }

    /**
     * Check if the response is an error
     *
     * @param array $response request response
     *
     * @return bool response is an error
     */
    public static function isErrorResponse($response)
    {
        return !is_array($response) || $response['error'];
    }

    /**
     * Return a success response
     *
     * @param string $content response content
     * @param string $status http status
     *
     * @return array error response
     */
    private static function getSuccessResponse($content, $status)
    {
        return [
            'message' => null,
            'content' => $content,
            'status' => $status,
            'error' => false,
        ];
    }

    /**
     * Get stream status
     *
     * @return string
     */
    private static function getFopenResponseStatus($stream)
    {
        $data = stream_get_meta_data($stream);
        $wrapperLines = $data['wrapper_data'];
        $matches = [];
        for ($i = count($wrapperLines); $i >= 1; --$i) {
            if (0 === strpos($wrapperLines[$i - 1], 'HTTP/1')) {
                preg_match('/(\d{3})/', $wrapperLines[$i - 1], $matches);
                break;
            }
        }

        return empty($matches) ? null : $matches[1];
    }

    private static function getCurlResponseStatus($curl)
    {
        $curlInfo = curl_getinfo($curl);

        return $curlInfo['http_code'];
    }
}
