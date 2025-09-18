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

namespace StripeOfficial\Controllers\admin;

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Psr\Container\ContainerInterface;
use StripeOfficial\Classes\services\PrestashopAdminTokenService;
use StripeOfficial\Classes\StripeProcessLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SkipCloudSyncDependenciesController extends StripeBaseAdminController
{
    public function __construct(ContainerInterface $container)
    {
        if (method_exists($this, 'setContainer')) {
            $this->setContainer($container);
        }
    }

    public function skipAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['_token'] ?? null;
        if (!PrestashopAdminTokenService::verifyToken('SkipCloudSyncDependencies', $token)) {
            return new JsonResponse(['error' => 'Invalid token'], 403);
        }

        $shopGroupId = \Stripe_official::getShopGroupIdContext();
        $shopId = \Stripe_official::getShopIdContext();
        $success = \Configuration::updateValue(\Stripe_official::WANTS_TO_USE_CLOUDSYNC, 0, false, $shopGroupId, $shopId);

        return new JsonResponse(['success' => $success]);
    }

    public function getAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['_token'] ?? null;
        if (!PrestashopAdminTokenService::verifyToken('SkipCloudSyncDependencies', $token)) {
            return new JsonResponse(['error' => 'Invalid token'], 403);
        }

        $shopGroupId = \Stripe_official::getShopGroupIdContext();
        $shopId = \Stripe_official::getShopIdContext();

        return new JsonResponse([
            'success' => \Configuration::updateValue(\Stripe_official::WANTS_TO_USE_CLOUDSYNC, 1, false, $shopGroupId, $shopId),
        ]);
    }

    public function clearCacheAction()
    {
        try {
            \Tools::clearAllCache();

            $kernel = SymfonyContainer::getInstance()->get('kernel');
            $filesystem = new \Symfony\Component\Filesystem\Filesystem();
            $cacheDir = $kernel->getCacheDir();
            $filesystem->remove($cacheDir);
        } catch (\Exception $ex) {
            StripeProcessLogger::logError('Clear cache after dependencies install failed with:' . $ex->getMessage(), 'stripe_official');
        }

        // Must use this format, otherwise will try to take something from cache and give error because just cleared and not found
        echo json_encode(['success' => true]);
        exit;
    }
}
