<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Container;

use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataCommand;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDao;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactory;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class DemodataCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private QueryBuilderFactory $queryBuilderFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilderFactory = new QueryBuilderFactory(new ConnectionProvider());

        $demodataDao = new DemodataDao(
            $this->queryBuilderFactory,
            new BasicContext(),
            new Filesystem()
        );

        $this->commandTester = new CommandTester(new DemodataCommand($demodataDao));
    }

    protected function tearDown(): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder->delete('oxarticles')->where('OXID LIKE "demodataTestArticle_"');
        $queryBuilder->execute();

        $basicContext = new BasicContext();
        if (file_exists($basicContext->getOutPath() . 'testfile')) {
            unlink($basicContext->getOutPath() . 'testfile');
        }
    }

    public function testExecuteDemodata(): void
    {
        $this->assertSame(0, $this->commandTester->execute([]));

        $this->assertFileExists((new BasicContext())->getOutPath() . '/testfile');

        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder->select('count(*) as count')
            ->from('oxarticles');

        $this->assertSame(2, (int)$queryBuilder->execute()->fetchColumn());
    }
}
