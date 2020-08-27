<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Framework\Module\Demodata;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\DemodataException;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\AggregateException;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Symfony\Component\Filesystem\Filesystem;

class DemodataDao implements DemodataDaoInterface
{
    private const DEMODATA_PACKAGE_NAME = 'oxideshop-demodata-%s';

    private const DEMODATA_PACKAGE_SOURCE_DIRECTORY = 'src';

    private const DEMODATA_PACKAGE_OUT_DIRECTORY = 'out';

    private const DEMODATA_SQL_FILENAME = 'demodata.sql';

    /**
     * @var QueryBuilderFactoryInterface
     */
    private $queryBuilderFactory;

    /** @var BasicContextInterface */
    private $basicContext;

    /**
     * @var Filesystem
     */
    private $filesystem = null;

    /**
     * @param QueryBuilderFactoryInterface $queryBuilderFactory
     * @param BasicContextInterface $context
     * @param Filesystem $filesystem
     */
    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        BasicContextInterface $context,
        Filesystem $filesystem
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;

        $this->basicContext = $context;

        $this->filesystem = $filesystem;
    }

    public function checkPreconditions(): void
    {
        $aggregateException = new AggregateException();

        $messages = [];
        if ($this->countOxuser() !== 0) {
            $messages[] = 'There are some submitted users in the shop. Please delete them and their dependencies as well.';
        }
        if ($this->countOxarticles() !== 0) {
            $messages[] = 'Please truncate the database table oxarticles.';
        }
        if ($this->countOxcategories() !== 0) {
            $messages[] = 'Please truncate the database table oxcategories.';
        }
        if (!file_exists($this->getActiveEditionDemodataPackageSqlFilePath())) {
            $messages[] = 'Package oxid-esales/' .
                       sprintf(self::DEMODATA_PACKAGE_NAME, strtolower($this->basicContext->getEdition())) .
                       ' was not found. Please ensure that demodata is available';
        }
        if (!is_readable($this->getActiveEditionDemodataPackageSqlFilePath())) {
            $messages[] = 'Demodata sql file is not readable (' .
                       $this->getActiveEditionDemodataPackageSqlFilePath() .
                       '). Please make it readable.';
        }

        if (count($messages)) {
            foreach ($messages as $message) {
                $aggregateException->add(new DemodataException($message));
            }
        }

        if ($aggregateException->hasExceptions()) {
            throw $aggregateException;
        }
    }

    private function countOxuser(): int
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder->select('count(*) as count')
            ->from('oxuser');

        return (int)$queryBuilder->execute()->fetchColumn();
    }

    private function countOxarticles(): int
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder->select('count(*) as count')
            ->from('oxarticles');

        return (int)$queryBuilder->execute()->fetchColumn();
    }

    private function countOxcategories(): int
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder->select('count(*) as count')
            ->from('oxcategories');

        return (int)$queryBuilder->execute()->fetchColumn();
    }


    private function getActiveEditionDemodataPackageSqlFilePath(): string
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [
                $this->getActiveEditionDemodataPackagePath(),
                self::DEMODATA_PACKAGE_SOURCE_DIRECTORY,
                self::DEMODATA_SQL_FILENAME
            ]
        );
    }

    private function getActiveEditionDemodataPackageFilePath(): string
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [
                $this->getActiveEditionDemodataPackagePath(),
                self::DEMODATA_PACKAGE_SOURCE_DIRECTORY,
                self::DEMODATA_PACKAGE_OUT_DIRECTORY
            ]
        );
    }

    private function getActiveEditionDemodataPackagePath(): string
    {
        $path = [];
        $vendorPath = $this->basicContext->getVendorPath();
        if ($vendorPath) {
            $path[] = $vendorPath;
        }
        
        array_push(
            $path,
            $this->basicContext->getComposerVendorName(),
            sprintf(
                self::DEMODATA_PACKAGE_NAME,
                strtolower($this->basicContext->getEdition())
            )
        );
            
        return implode(DIRECTORY_SEPARATOR, $path);
    }

    public function applyDemodata(): void
    {
        $this->runSql();
        $this->copyOutFiles();
    }

    private function runSql(): void
    {
        $dbConnection = $this->queryBuilderFactory->create()->getConnection();

        $queries = file_get_contents($this->getActiveEditionDemodataPackageSqlFilePath());
        $tables = [];
        preg_match_all('/INSERT INTO `([a-z\d]*)` .*/m', $queries, $tables);

        $platform   = $dbConnection->getDatabasePlatform();
        foreach ($tables[1] as $tableToTruncate) {
            $dbConnection->executeUpdate($platform->getTruncateTableSQL($tableToTruncate, true));
        }
        $dbConnection->exec($queries);
    }

    private function copyOutFiles(): void
    {
        $this->filesystem->mirror(
            $this->getActiveEditionDemodataPackageFilePath(),
            $this->basicContext->getOutPath()
        );
    }
}
