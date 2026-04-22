<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Integration\Framework\Module\Demodata;

use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDao;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\AggregateException;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Tests\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\DatabaseTrait;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class DemodataDaoTest extends TestCase
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

    #[DoesNotPerformAssertions]
    public function testCheckPreconditions(): void
    {
        (new DemodataDao(
            $this->connectionFactory,
            $this->queryBuilderFactory,
            $this->getContext(),
            new Filesystem()
        ))->checkPreconditions();
    }

    public function testCheckPreconditionsWithExistingUsers(): void
    {
        $this->insertUsers();

        $this->expectException(AggregateException::class);

        (new DemodataDao(
            $this->connectionFactory,
            $this->queryBuilderFactory,
            $this->getContext(),
            new Filesystem()
        ))->checkPreconditions();
    }

    public function testCheckPreconditionsWithExistingCategories(): void
    {
        $this->insertCategories();

        $this->expectException(AggregateException::class);

        (new DemodataDao(
            $this->connectionFactory,
            $this->queryBuilderFactory,
            $this->getContext(),
            new Filesystem()
        ))->checkPreconditions();
    }

    public function testCheckPreconditionsWithExistingProducts(): void
    {
        $this->insertProducts();

        $this->expectException(AggregateException::class);

        (new DemodataDao(
            $this->connectionFactory,
            $this->queryBuilderFactory,
            $this->getContext(),
            new Filesystem()
        ))->checkPreconditions();
    }

    public function testCheckPreconditionWithMultipleErrors(): void
    {
        $this->insertUsers();
        $this->insertCategories();
        $this->insertProducts();
        try {
            (new DemodataDao(
                $this->connectionFactory,
                $this->queryBuilderFactory,
                $this->getContext(),
                new Filesystem()
            ))->checkPreconditions();
        } catch (AggregateException $aggregateException) {
            $this->assertCount(3, $aggregateException->getExceptions());
        }
    }

    public function testCheckPreconditionWithDemodataSourceFilesInaccessible(): void
    {
        $this->expectException(AggregateException::class);

        (new DemodataDao(
            $this->connectionFactory,
            $this->queryBuilderFactory,
            $this->getContextWithVendorPath('some-non-existing-path'),
            new Filesystem()
        ))->checkPreconditions();
    }

    public function testApplyDemodataCopiesFilesAndRunsSQL(): void
    {
        (new DemodataDao(
            $this->connectionFactory,
            $this->queryBuilderFactory,
            $this->getContext(),
            new Filesystem()
        ))->applyDemodata();

        $this->assertFileExists($this->testFile);

        $demoProductsCount = $this->queryBuilderFactory
            ->create()
            ->select('count(*) as count')
            ->from('oxarticles')
            ->fetchOne();

        $this->assertSame(2, (int)$demoProductsCount);
    }

    private function getContext(): BasicContextInterface
    {
        return $this->getContextWithVendorPath(Path::join(__DIR__, 'Fixtures'));
    }

    private function getContextWithVendorPath(string $vendorPath): BasicContextInterface
    {
        return $this->createConfiguredStub(BasicContextInterface::class, [
            'getVendorPath' => $vendorPath,
            'getComposerVendorName' => 'oxid-esales',
            'getEdition' => Edition::Community,
            'getOutPath' => $this->outPath,
        ]);
    }

    private function insertProducts(): void
    {
        $this->queryBuilderFactory
            ->create()
            ->insert('oxarticles')
            ->values(['OXID' => ':oxid'])
            ->setParameter('oxid', 'test_article')
            ->executeStatement();
    }

    private function insertCategories(): void
    {
        $this->queryBuilderFactory
            ->create()
            ->insert('oxcategories')
            ->values(['OXID' => ':oxid'])
            ->setParameter('oxid', 'test_category')
            ->executeStatement();
    }

    private function insertUsers(): void
    {
        $this->queryBuilderFactory
            ->create()
            ->insert('oxuser')
            ->values(['OXID' => ':oxid'])
            ->setParameter('oxid', 'test_category')
            ->executeStatement();
    }
}
