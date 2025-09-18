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

class ModuleSource
{
    /** @var string */
    private $name;

    /** @var string */
    private $newVersion;

    /** @var string */
    private $path;

    /** @var bool */
    private $unzipable;

    public function __construct(string $name, string $newVersion, string $path, bool $unzipable)
    {
        $this->name = $name;
        $this->newVersion = $newVersion;
        $this->path = $path;
        $this->unzipable = $unzipable;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isZipped(): bool
    {
        return $this->unzipable;
    }

    /** @return array<string, string|boolean> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'newVersion' => $this->newVersion,
            'path' => $this->path,
            'unzipable' => $this->unzipable,
        ];
    }
}
