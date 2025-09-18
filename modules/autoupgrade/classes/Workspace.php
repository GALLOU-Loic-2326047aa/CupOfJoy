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

namespace PrestaShop\Module\AutoUpgrade;

use PrestaShop\Module\AutoUpgrade\UpgradeTools\Translator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class Workspace
{
    /** @var Translator */
    private $translator;

    /**
     * @var string[] List of paths used by autoupgrade
     */
    private $paths;

    /** @var Filesystem */
    private $filesystem;

    /**
     * @param string[] $paths
     */
    public function __construct(Translator $translator, Filesystem $filesystem, array $paths)
    {
        $this->translator = $translator;
        $this->filesystem = $filesystem;
        $this->paths = $paths;
    }

    /**
     * @throws IOException
     */
    public function createFolders(): void
    {
        foreach ($this->paths as $path) {
            try {
                $this->filesystem->mkdir($path);
            } catch (IOException $e) {
                throw new IOException($this->translator->trans('Unable to create directory %s: %s', [$path, $e->getMessage()]));
            }

            if (!is_writable($path)) {
                throw new IOException($this->translator->trans('Cannot write to the directory. Please ensure you have the necessary write permissions on "%s".', [$path]));
            }

            $this->createPhpIndex($path);
        }
    }

    /**
     * @throws IOException
     */
    public function createPhpIndex(string $directoryPath): void
    {
        $filePath = $directoryPath . DIRECTORY_SEPARATOR . 'index.php';
        if (!$this->filesystem->exists($filePath)) {
            $this->filesystem->copy(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'index.php', $filePath);
        }
    }

    /**
     * @throws IOException
     */
    public function createHtAccess(string $modulePath): void
    {
        $filePath = $modulePath . DIRECTORY_SEPARATOR . '.htaccess';

        if (!$this->filesystem->exists($filePath)) {
            $content = <<<HTACCESS
<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{REQUEST_URI} ^.*/autoupgrade/logs/ [NC]
    RewriteCond %{REQUEST_URI} !\.txt$ [NC]
    RewriteRule ^ - [F]

    RewriteRule ^ - [L]
</IfModule>
HTACCESS;
            $this->filesystem->dumpFile($filePath, $content);
        }
    }

    /**
     * @throws IOException
     */
    public function init(string $modulePath): void
    {
        $this->createFolders();
        $this->createHtAccess($modulePath);
    }
}
