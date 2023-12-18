<?php

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Setup\Demodata;

use Symfony\Component\Filesystem\Filesystem;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactory;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDao;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\AggregateException;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionProvider;

final class DemodataDaoTest extends TestCase
{
    private QueryBuilderFactory $queryBuilderFactory;

    protected function setUp(): void
    {
        $this->queryBuilderFactory = new QueryBuilderFactory(new ConnectionProvider());
    }

    protected function tearDown(): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder->delete('oxcategories')->where('OXID = "test_category"');
        $queryBuilder->execute();
        $queryBuilder->delete('oxarticles')->where('OXID = "test_article"');
        $queryBuilder->execute();

        $facts = (new BasicContext())->getFacts();
        if (file_exists($facts->getOutPath() . 'testfile')) {
            unlink($facts->getOutPath() . 'testfile');
        }
    }

    public function testCheckPreconditions(): void
    {
        $demodataDao = new DemodataDao(
            $this->queryBuilderFactory,
            $this->createContext(),
            new Filesystem()
        );

        try {
            $demodataDao->checkPreconditions();
        } catch (AggregateException $e) {
            $this->assertTrue(false, 'There should not be an AggregateException.');
        }

        $this->assertTrue(true);
    }

    public function testCheckPreconditionsFailsCategories(): void
    {
        $demodataDao = new DemodataDao(
            $this->queryBuilderFactory,
            $this->createContext(),
            new Filesystem()
        );

        $queryBuilder =  $this->queryBuilderFactory->create();
        $queryBuilder->insert('oxcategories')
            ->values(['OXID' => ':oxid'])
            ->setParameter(':oxid', 'test_category');

        $queryBuilder->execute();

        $this->expectException(AggregateException::class);
        $demodataDao->checkPreconditions();
    }

    public function testCheckPreconditionsFailsArticles(): void
    {
        $demodataDao = new DemodataDao(
            $this->queryBuilderFactory,
            $this->createContext(),
            new Filesystem()
        );

        $queryBuilder =  $this->queryBuilderFactory->create();
        $queryBuilder->insert('oxarticles')
            ->values(['OXID' => ':oxid'])
            ->setParameter(':oxid', 'test_article');
        $queryBuilder->execute();

        $this->expectException(AggregateException::class);
        $demodataDao->checkPreconditions();
    }

    public function testCheckPreconditionsFailsDemodata(): void
    {
        $demodataDao = new DemodataDao(
            $this->queryBuilderFactory,
            $this->createContext('somewhere/NotReadable'),
            new Filesystem()
        );

        try {
            $demodataDao->checkPreconditions();
        } catch (AggregateException $aggException) {
            $this->assertSame(2, count($aggException->getExceptions()));
        }
    }

    public function testApplyDemodataCopiesFilesAndRunSQL(): void
    {
        $demodataDao = new DemodataDao(
            $this->queryBuilderFactory,
            $this->createContext(),
            new Filesystem()
        );

        $demodataDao->applyDemodata();

        $facts = (new BasicContext())->getFacts();
        $this->assertFileExists($facts->getOutPath() . '/testfile');

        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder->select('count(*) as count')
            ->from('oxarticles');

        $this->assertSame(2, (int)$queryBuilder->execute()->fetchColumn());
    }

    private function createContext($vendorPath = __DIR__ . '/Fixtures'): BasicContextInterface
    {
        $context = $this->getMockBuilder(BasicContext::class)
            ->onlyMethods(['getVendorPath', 'getEdition'])
            ->getMock();

        $context->expects($this->any())->method('getVendorPath')->willReturn($vendorPath);
        $context->expects($this->any())->method('getEdition')->willReturn('CE');

        return $context;
    }
}
