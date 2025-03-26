<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Integration\Framework\Module\Demodata;

use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDao;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\AggregateException;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use OxidEsales\EshopCommunity\Tests\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\DatabaseTrait;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\BasicContextStub;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ContextStub;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class DemodataDaoTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTrait;

    private QueryBuilderFactory $queryBuilderFactory;
    private string $testFile;

    public function setUp(): void
    {
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

    #[DoesNotPerformAssertions]
    public function testCheckPreconditions(): void
    {
        (new DemodataDao(
            $this->queryBuilderFactory,
            $this->getContextStub(),
            new Filesystem()
        ))->checkPreconditions();
    }

    public function testCheckPreconditionsWithExistingUsers(): void
    {
        $this->insertUsers();

        $this->expectException(AggregateException::class);

        (new DemodataDao(
            $this->queryBuilderFactory,
            $this->getContextStub(),
            new Filesystem()
        ))->checkPreconditions();
    }

    public function testCheckPreconditionsWithExistingCategories(): void
    {
        $this->insertCategories();

        $this->expectException(AggregateException::class);

        (new DemodataDao(
            $this->queryBuilderFactory,
            $this->getContextStub(),
            new Filesystem()
        ))->checkPreconditions();
    }

    public function testCheckPreconditionsWithExistingProducts(): void
    {
        $this->insertProducts();

        $this->expectException(AggregateException::class);

        (new DemodataDao(
            $this->queryBuilderFactory,
            $this->getContextStub(),
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
                $this->queryBuilderFactory,
                $this->getContextStub(),
                new Filesystem()
            ))->checkPreconditions();
        } catch (AggregateException $aggregateException) {
            $this->assertCount(3, $aggregateException->getExceptions());
        }
    }

    public function testCheckPreconditionWithDemodataSourceFilesInaccessible(): void
    {
        $context = $this->getContextStub();
        $context->setVendorPath('some-non-existing-path');

        $this->expectException(AggregateException::class);

        (new DemodataDao(
            $this->queryBuilderFactory,
            $context,
            new Filesystem()
        ))->checkPreconditions();
    }

    public function testApplyDemodataCopiesFilesAndRunsSQL(): void
    {
        (new DemodataDao(
            $this->queryBuilderFactory,
            $this->getContextStub(),
            new Filesystem()
        ))->applyDemodata();

        $this->assertFileExists($this->testFile);

        $demoProductsCount = $this->queryBuilderFactory
            ->create()
            ->select('count(*) as count')
            ->from('oxarticles')
            ->fetchFirstColumn();

        $this->assertEquals(2, $demoProductsCount);
    }

    private function getContextStub(): BasicContextStub
    {
        $context = new ContextStub();
        $context->setVendorPath(Path::join(__DIR__, '/Fixtures'));
        $context->setEdition(Edition::Community);

        return $context;
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
