<?php

/**
 * Copyright (c) since 2010 Stripe, Inc. (https://stripe.com)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Stripe <https://support.stripe.com/contact/email>
 * @copyright Since 2010 Stripe, Inc.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

use StripeOfficial\Classes\StripeProcessLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripePdoLockService
{
    protected $key;
    protected $ttl;

    const TABLE_NAME = _DB_PREFIX_ . 'stripe_lock_keys';

    public function __construct($key, $ttl)
    {
        $this->key = $key;
        $this->ttl = $ttl;
    }

    private static function loadDependencies()
    {
        // Prestashop has its own symfony package, which is deprecated in 1.7, so this makes sure to use the proper package
        require_once _PS_MODULE_DIR_ . 'stripe_official/vendor/symfony/lock/LockFactory.php';
        require_once _PS_MODULE_DIR_ . 'stripe_official/vendor/symfony/lock/PersistingStoreInterface.php';
        require_once _PS_MODULE_DIR_ . 'stripe_official/vendor/symfony/lock/Store/PdoStore.php';
        require_once _PS_MODULE_DIR_ . 'stripe_official/vendor/symfony/lock/LockInterface.php';
        require_once _PS_MODULE_DIR_ . 'stripe_official/vendor/symfony/lock/Lock.php';
    }

    public function executeUnderLock(callable $executable)
    {
        self::loadDependencies();
        $lock = null;
        try {
            $pdo = Db::getInstance()->connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $store = new Symfony\Component\Lock\Store\PdoStore($pdo, ['db_table' => self::TABLE_NAME]);
            $factory = new Symfony\Component\Lock\LockFactory($store);

            $lock = $factory->createLock($this->key, $this->ttl);
            $lock->acquire();
        } catch (Exception $e) {
            StripeProcessLogger::logError(
                'Exception during lock acquire for:' . $this->key . ' Error: ' . $e->getMessage() . ' ' . ($e->getPrevious() ? $e->getPrevious()->getMessage() : ''),
                'StripePdoLockService'
            );
        }

        if (isset($lock) && $lock->isAcquired()) {
            // Run this part of code only if everything is set, and locking is working perfectly
            try {
                $executable();
            } catch (Throwable $e) {
                throw $e;
            } finally {
                $lock->release();
            }
        } elseif (!isset($lock)) {
            // If something went wrong during lock setup, execute anyway. Worst case will be executed multiple times, so keeps the old behaviour
            $executable();
        }
    }

    public static function createTable()
    {
        self::loadDependencies();
        try {
            $pdo = Db::getInstance()->connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $store = new Symfony\Component\Lock\Store\PdoStore($pdo, ['db_table' => self::TABLE_NAME]);
            $store->createTable();
        } catch (Exception $e) {
            StripeProcessLogger::logError(
                'Exception during table creation:' . $e->getMessage(),
                'StripePdoLockService'
            );
        }
    }
}
