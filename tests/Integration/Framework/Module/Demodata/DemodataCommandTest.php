<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Integration\Framework\Module\Demodata;

use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataCommand;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDao;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use OxidEsales\EshopCommunity\Tests\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\DatabaseTrait;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\BasicContextStub;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ContextStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class DemodataCommandTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTrait;

    private QueryBuilderFactory $queryBuilderFactory;
    private string $testFile;

    public function setUp(): void
    {
        parent::setUp();

        $this->queryBuilderFactory = $this->get(QueryBuilderFactoryInterface::class);
        $this->testFile = Path::join((new BasicContext())->getOutPath(), 'testfile');
    }

    public function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
        $this->setupShopDatabase();
    }

    public function testExecuteDemodata(): void
    {
        $commandReturnCode = (new CommandTester(
            new DemodataCommand(
                new DemodataDao(
                    $this->queryBuilderFactory,
                    $this->getContextStub(),
                    new Filesystem()
                )
            )
        ))->execute([]);

        $demoProductsCount = $this->queryBuilderFactory
            ->create()
            ->select('count(*) as count')
            ->from('oxarticles')
            ->fetchFirstColumn();

        $this->assertSame(0, $commandReturnCode);
        $this->assertFileExists($this->testFile);
        $this->assertEquals(2, $demoProductsCount);
    }

    private function getContextStub(): BasicContextStub
    {
        $context = new ContextStub();
        $context->setVendorPath(Path::join(__DIR__, '/Fixtures'));
        $context->setEdition(Edition::Community);

        return $context;
    }
}
