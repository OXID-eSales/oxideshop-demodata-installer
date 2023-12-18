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
use OxidEsales\Facts\Facts;
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
            $this->createContext(),
            new Filesystem()
        );

        $this->commandTester = new CommandTester(new DemodataCommand($demodataDao));
    }

    protected function tearDown(): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder->delete('oxarticles')->where('OXID LIKE "demodataTestArticle_"');
        $queryBuilder->execute();

        $facts = (new BasicContext())->getFacts();
        if (file_exists($facts->getOutPath() . 'testfile')) {
            unlink($facts->getOutPath() . 'testfile');
        }
    }

    public function testExecuteDemodata(): void
    {
        $this->assertSame(0, $this->commandTester->execute([]));

        $facts = (new BasicContext())->getFacts();
        $this->assertFileExists($facts->getOutPath() . '/testfile');

        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder->select('count(*) as count')
            ->from('oxarticles');

        $this->assertSame(2, (int)$queryBuilder->execute()->fetchColumn());
    }

    private function createContext($vendorPath = __DIR__ . '/Fixtures'): BasicContext
    {
        $context = $this->getMockBuilder(BasicContext::class)->onlyMethods(['getFacts'])->getMock();
        $facts = $this->getMockBuilder(Facts::class)->onlyMethods(['getVendorPath', 'getEdition'])->getMock();

        $facts->expects($this->any())->method('getVendorPath')->willReturn($vendorPath);
        $facts->method('getEdition')->willReturn('CE');

        $context->expects($this->any())->method('getFacts')->willReturn($facts);

        return $context;
    }
}
