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

namespace PrestaShop\Module\AutoUpgrade\Twig\Steps;

use PrestaShop\Module\AutoUpgrade\UpgradeTools\Translator;

class RestoreSteps implements StepsInterface
{
    const STEP_BACKUP_SELECTION = 'backup-selection';
    const STEP_RESTORE = 'restore';
    const STEP_POST_RESTORE = 'post-restore';

    /** @var Translator */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function getSteps(): array
    {
        return [
            self::STEP_BACKUP_SELECTION => [
                'title' => $this->translator->trans('Backup selection'),
            ],
            self::STEP_RESTORE => [
                'title' => $this->translator->trans('Restore'),
            ],
            self::STEP_POST_RESTORE => [
                'title' => $this->translator->trans('Post-restore'),
            ],
        ];
    }
}
