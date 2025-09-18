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

namespace PrestaShop\Module\AutoUpgrade\Commands;

use Exception;
use InvalidArgumentException;
use PrestaShop\Module\AutoUpgrade\Task\ExitCode;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteBackupCommand extends AbstractBackupCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'backup:delete';

    protected function configure(): void
    {
        $this
            ->setDescription('Delete a store backup file.')
            ->setHelp(
                'This command allows you to delete a store backup file.'
            )
            ->addArgument('admin-dir', InputArgument::REQUIRED, 'The admin directory name.')
            ->addOption('backup', null, InputOption::VALUE_REQUIRED, 'Specify the backup name to delete. The allowed values can be found with backup:list command)');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        try {
            $this->setupEnvironment($input, $output);

            $backup = $input->getOption('backup');
            $exitCode = ExitCode::SUCCESS;

            if (!$backup) {
                if (!$input->isInteractive()) {
                    throw new InvalidArgumentException("The '--backup' option is required.");
                }

                $backup = $this->selectBackupInteractive($input, $output);

                if (!$backup) {
                    return $exitCode;
                }
            }

            $this->backupManager->deleteBackup($backup);
            $this->logger->info('The backup file has been successfully deleted');

            $this->logger->debug('Process completed with exit code: ' . $exitCode);

            return $exitCode;
        } catch (Exception $e) {
            $this->logger->error("An error occurred during the delete backup process:\n" . $e);
            throw $e;
        }
    }
}
