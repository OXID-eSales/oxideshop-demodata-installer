<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Framework\Module\Demodata;

use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDaoInterface;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\AggregateException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DemodataCommand extends Command
{
    /**
     * @var DemodataDaoInterface
     */
    private $demodataDao;

    public function __construct(
        DemodataDaoInterface $demodataDao
    ) {
        parent::__construct();

        $this->demodataDao = $demodataDao;
    }

    protected function configure()
    {
        $this->setDescription('Performs installation of demodata for active shopversion');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Running precondition checks...</info>');
        try {
            $this->demodataDao->checkPreconditions();
            $output->writeln('<info>Applying demodata</info>');
            $this->demodataDao->applyDemodata();
        } catch (AggregateException $aggregateException) {
            $message = 'We found problems which prevent the execution of the command, please fix them:';
            $output->writeln('<error>' . $message . '</error>');
            $exeptions = $aggregateException->getExceptions();
            foreach ($exeptions as $exeption) {
                $output->writeln('<error> - ' . $exeption->getMessage() . '</error>');
            }
        }

        return 0;
    }
}
