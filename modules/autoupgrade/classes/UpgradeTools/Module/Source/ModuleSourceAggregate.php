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

namespace PrestaShop\Module\AutoUpgrade\UpgradeTools\Module\Source;

use PrestaShop\Module\AutoUpgrade\UpgradeTools\Module\ModuleDownloaderContext;
use PrestaShop\Module\AutoUpgrade\UpgradeTools\Module\Source\Provider\AbstractModuleSourceProvider;

class ModuleSourceAggregate
{
    /** @var AbstractModuleSourceProvider[] */
    private $providers;

    /**
     * @param AbstractModuleSourceProvider[] $sourceProviders Ordered by priority (first provider has top priority)
     */
    public function __construct(array $sourceProviders)
    {
        $this->providers = $sourceProviders;
    }

    public function setSourcesIn(ModuleDownloaderContext $moduleContext): void
    {
        $updateSources = [];
        foreach ($this->providers as $provider) {
            $updateSources = array_merge(
                $updateSources,
                $provider->getUpdatesOfModule(
                    $moduleContext->getModuleName(),
                    $moduleContext->getReferenceVersion()
                ));
        }

        $moduleContext->setUpdateSources(
            $this->orderSources($updateSources)
        );
    }

    /**
     * @param ModuleSource[] $sources
     *
     * @return ModuleSource[]
     */
    private function orderSources(array $sources): array
    {
        usort($sources, function (ModuleSource $source1, ModuleSource $source2) {
            return version_compare($source2->getNewVersion(), $source1->getNewVersion());
        });

        return $sources;
    }
}
