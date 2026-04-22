<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Integration\Framework\Module\Demodata;

use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataCommand;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDao;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Tests\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\DatabaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class DemodataCommandTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTrait;

    private ConnectionFactoryInterface $connectionFactory;
    private QueryBuilderFactory $queryBuilderFactory;
    private string $outPath;
    private string $testFile;

    public function setUp(): void
    {
        parent::setUp();

        $this->connectionFactory = $this->get(ConnectionFactoryInterface::class);
        $this->queryBuilderFactory = $this->get(QueryBuilderFactoryInterface::class);
        $this->outPath = (new BasicContext())->getOutPath();
        $this->testFile = Path::join($this->outPath, 'testfile');
    }

    public function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
        $this->setupShopDatabase();

        parent::tearDown();
    }

    public function testExecuteDemodata(): void
    {
        $commandReturnCode = (new CommandTester(
            new DemodataCommand(
                new DemodataDao(
                    $this->connectionFactory,
                    $this->queryBuilderFactory,
                    $this->getContext(),
                    new Filesystem()
                )
            )
        ))->execute([]);

        $demoProductsCount = $this->queryBuilderFactory
            ->create()
            ->select('count(*) as count')
            ->from('oxarticles')
            ->fetchOne();

        $this->assertSame(0, $commandReturnCode);
        $this->assertFileExists($this->testFile);
        $this->assertSame(2, (int)$demoProductsCount);
    }

    private function getContext(): BasicContextInterface
    {
        return $this->createConfiguredStub(BasicContextInterface::class, [
            'getVendorPath' => Path::join(__DIR__, 'Fixtures'),
            'getComposerVendorName' => 'oxid-esales',
            'getEdition' => Edition::Community,
            'getOutPath' => $this->outPath,
        ]);
    }
}
