<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
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
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\AutoUpgrade\Router;

use PrestaShop\Module\AutoUpgrade\UpgradeContainer;

class MiddlewareHandler
{
    /**
     * @var UpgradeContainer
     */
    protected $upgradeContainer;

    public function __construct(UpgradeContainer $upgradeContainer)
    {
        $this->upgradeContainer = $upgradeContainer;
    }

    /**
     * @param Routes::* $routeName
     *
     * @return Routes::*
     */
    public function process(string $routeName): string
    {
        $route = RoutesConfig::ROUTES[$routeName];
        $routeMiddlewares = $route['middleware'] ?? [];

        $nextRoute = $routeName;

        foreach ($routeMiddlewares as $middleware) {
            $processedRoute = (new $middleware($this->upgradeContainer))->process();

            if ($processedRoute !== null) {
                $nextRoute = $processedRoute;
                break;
            }
        }

        return $nextRoute;
    }
}
