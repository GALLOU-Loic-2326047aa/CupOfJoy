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
/**
 * Contains code for auth util class.
 */

namespace LaPoste\LaPosteProExpeditionsPrestashop\Util;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Auth util class.
 *
 * Helper to manage API auth.
 */
class AuthUtil
{
    /**
     * API request validation.
     *
     * @param string $body encrypted body
     *
     * @return mixed
     */
    public static function authenticate($body)
    {
        return null === self::decryptBody($body) ? ApiUtil::sendApiResponse(401) : true;
    }

    /**
     * API request validation with access key check.
     *
     * @param string $body encrypted body
     *
     * @return mixed
     */
    public static function authenticateAccessKey($body)
    {
        $decryptedBody = self::decryptBody($body);
        if (null === $decryptedBody) {
            LoggerUtil::warn('Incoming request authentication failed (401)');
            return ApiUtil::sendApiResponse(401);
        }

        if (is_object($decryptedBody) && property_exists($decryptedBody, 'accessKey')
            && self::getAccessKey(ShopUtil::$shopGroupId, ShopUtil::$shopId) === $decryptedBody->accessKey) {
            return true;
        }

        LoggerUtil::warn('Incoming request authentication failed (403)');
        return ApiUtil::sendApiResponse(403);
    }

    /**
     * Is plugin paired.
     *
     * @param int $shopGroupId shop group id
     * @param int $shopId shop id
     *
     * @return bool
     */
    public static function isPluginPaired($shopGroupId, $shopId)
    {
        return null !== self::getAccessKey($shopGroupId, $shopId) && null !== self::getSecretKey($shopGroupId, $shopId);
    }

    /**
     * Can use plugin.
     *
     * @return bool
     */
    public static function canUsePlugin()
    {
        return false !== self::isPluginPaired(ShopUtil::$shopGroupId, ShopUtil::$shopId)
            && null === ConfigurationUtil::get('LP_PAIRING_UPDATE');
    }

    /**
     * Pair plugin.
     *
     * @param string $accessKey API access key
     * @param string $secretKey API secret key
     *
     * @void
     */
    public static function pairPlugin($accessKey, $secretKey)
    {
        ConfigurationUtil::set('LP_ACCESS_KEY', $accessKey);
        ConfigurationUtil::set('LP_SECRET_KEY', $secretKey);
    }

    /**
     * Request body decryption.
     *
     * @param string $jsonBody encrypted body
     *
     * @return object|null
     */
    public static function decryptBody($jsonBody)
    {
        $body = json_decode($jsonBody);

        if (null === $body || !is_object($body) || !property_exists($body, 'encryptedKey')
            || !property_exists($body, 'encryptedData')) {
            return null;
        }

        $key = self::decryptPublicKey($body->encryptedKey);

        if (null === $key) {
            return null;
        }

        $data = self::encryptRc4(base64_decode($body->encryptedData), $key);

        return json_decode($data);
    }

    /**
     * Request body decryption.
     *
     * @param mixed $body encrypted body
     *
     * @return mixed
     */
    public static function encryptBody($body)
    {
        $key = self::getRandomKey();
        if (null === $key) {
            return null;
        }

        return json_encode(
            [
                'encryptedKey' => MiscUtil::base64OrNull(self::encryptPublicKey($key)),
                'encryptedData' => MiscUtil::base64OrNull(
                    self::encryptRc4(is_array($body) ? json_encode($body) : $body, $key)
                ),
            ]
        );
    }

    /**
     * Get random bytes from open ssl
     *
     * @return string|false
     */
    public static function getOpensslRandomPseudoBytes()
    {
        return openssl_random_pseudo_bytes(200);
    }

    /**
     * Get random encryption key.
     *
     * @return string|null
     */
    public static function getRandomKey()
    {
        $randomKey = self::getOpensslRandomPseudoBytes();

        if (false === $randomKey) {
            return null;
        }

        return bin2hex($randomKey);
    }

    /**
     * Encrypt with public key.
     *
     * @param string $str string to encrypt
     *
     * @return array|null bytes array
     */
    public static function encryptPublicKey($str)
    {
        $publicKey = \Tools::file_get_contents(realpath(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR
            . 'resource' . DIRECTORY_SEPARATOR . 'publickey');
        $encrypted = '';
        if (openssl_public_encrypt($str, $encrypted, $publicKey)) {
            return $encrypted;
        }

        return null;
    }

    /**
     * Decrypt with public key.
     *
     * @param string $str string to encrypt
     *
     * @return mixed|null
     */
    public static function decryptPublicKey($str)
    {
        $publicKey = \Tools::file_get_contents(realpath(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR
            . 'resource' . DIRECTORY_SEPARATOR . 'publickey');
        $decrypted = '';
        if (openssl_public_decrypt(base64_decode($str), $decrypted, $publicKey)) {
            return json_decode($decrypted);
        }

        return null;
    }

    /**
     * RC4 symmetric cipher encryption/decryption
     *
     * @param string $str string to be encrypted/decrypted
     * @param string $key secret key for encryption/decryption
     *
     * @return string bytes array
     */
    public static function encryptRc4($str, $key)
    {
        $s = [];
        for ($i = 0; $i < 256; ++$i) {
            $s[$i] = $i;
        }
        $j = 0;
        for ($i = 0; $i < 256; ++$i) {
            $j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
            $x = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
        }
        $i = 0;
        $j = 0;
        $res = '';
        $length = strlen($str);
        for ($y = 0; $y < $length; ++$y) {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            $x = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
            $res .= $str[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
        }

        return $res;
    }

    /**
     * Get access key.
     *
     * @param int $shopGroupId shop group id
     * @param int $shopId shop id
     *
     * @return string|null
     */
    public static function getAccessKey($shopGroupId, $shopId)
    {
        return ConfigurationUtil::get('LP_ACCESS_KEY', $shopGroupId, $shopId);
    }

    /**
     * Get secret key.
     *
     * @param int $shopGroupId shop group id
     * @param int $shopId shop id
     *
     * @return string|null
     */
    public static function getSecretKey($shopGroupId, $shopId)
    {
        return ConfigurationUtil::get('LP_SECRET_KEY', $shopGroupId, $shopId);
    }
}
