<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Framework\Module\Demodata;

use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\AggregateException;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\DemodataException;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function sprintf;
use function strtolower;

class DemodataDao implements DemodataDaoInterface
{
    public function __construct(
        private readonly QueryBuilderFactoryInterface $queryBuilderFactory,
        private readonly BasicContextInterface $basicContext,
        private readonly Filesystem $filesystem
    ) {
    }

    public function checkPreconditions(): void
    {
        $messages = [];
        if ($this->hasRecords('oxuser')) {
            $messages[] =
                'There are some submitted users in the shop. Please delete them and their dependencies as well.';
        }
        if ($this->hasRecords('oxarticles')) {
            $messages[] = 'Please truncate the database table oxarticles.';
        }
        if ($this->hasRecords('oxcategories')) {
            $messages[] = 'Please truncate the database table oxcategories.';
        }
        if (!is_readable($this->getDemodataSqlDump())) {
            $messages[] = sprintf(
                'Error reading Demodata SQL file (%s). ' .
                'Please make sure that demodata is available and file is readable.',
                $this->getDemodataSqlDump()
            );
        }

        if ($messages) {
            $aggregateException = new AggregateException();
            foreach ($messages as $message) {
                $aggregateException->add(new DemodataException($message));
            }
            throw $aggregateException;
        }
    }

    public function applyDemodata(): void
    {
        $this->runSql();
        $this->copyOutFiles();
    }

    private function getDemodataSqlDump(): string
    {
        return Path::join(
            $this->getDemodataSourcePath(),
            'demodata.sql'
        );
    }

    private function runSql(): void
    {
        $dbConnection = $this->queryBuilderFactory->create()->getConnection();

        $queries = file_get_contents($this->getDemodataSqlDump());
        $tables = [];
        preg_match_all('/INSERT INTO `([a-z\d]*)` .*/m', $queries, $tables);

        $platform = $dbConnection->getDatabasePlatform();
        foreach ($tables[1] as $tableToTruncate) {
            $dbConnection->executeUpdate($platform->getTruncateTableSQL($tableToTruncate, true));
        }
        $dbConnection->exec($queries);
    }

    private function copyOutFiles(): void
    {
        $this->filesystem->mirror(
            Path::join(
                $this->getDemodataSourcePath(),
                'out'
            ),
            $this->basicContext->getOutPath()
        );
    }

    private function getDemodataSourcePath(): string
    {
        return Path::join(
            $this->basicContext->getVendorPath(),
            $this->basicContext->getComposerVendorName(),
            sprintf(
                'oxideshop-demodata-%s',
                strtolower($this->basicContext->getEdition()->value)
            ),
            'src',
        );
    }

    private function hasRecords(string $table): bool
    {
        return (bool)$this->queryBuilderFactory
            ->create()
            ->select('count(*) as count')
            ->from($table)
            ->execute()
            ->fetchColumn();
    }
}
