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

namespace Bant\BantGorgias\Api;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Api
{
    const BASE_URL = 'https://gorgias.blueant-solutions.com/';
    private $shopId;
    public $client;
    public $endPoint;
    public $shopUrl;

    public function __construct($shopId, $shopUrl, $endPoint = '')
    {
        $this->shopId = $shopId;
        $this->shopUrl = $shopUrl;
        $this->endPoint = $endPoint;
    }

    public function get()
    {
        if (!function_exists('curl_init')) {
            exit('cURL extension is not installed');
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json',  'Authorization: Bearer ' . $this->shopId . ':' . $this->shopUrl]);
        curl_setopt($curl, CURLOPT_URL, self::BASE_URL . $this->endPoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($curl);
        curl_close($curl);

        return json_decode($result, true, 512);
    }

    public function post($data)
    {
        if (!function_exists('curl_init')) {
            exit('cURL extension is not installed');
        }
        $data = json_encode($data);

        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json',
                    'Content-Length: ' . mb_strlen($data),
                    'Authorization: Bearer ' . \BantGorgias::TOKEN,
                ]
            );

            curl_setopt($curl, CURLOPT_URL, self::BASE_URL . $this->endPoint);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $result = curl_exec($curl);

            curl_close($curl);
        } catch (
            \Exception $e) {
                trigger_error(sprintf(
                    'Curl failed with error #%d: %s',
                    $e->getCode(), $e->getMessage()),
                    E_USER_ERROR);
            }

        return $result;
    }
}
