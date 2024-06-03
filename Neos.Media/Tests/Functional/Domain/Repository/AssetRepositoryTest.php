<?php
namespace Neos\Media\Tests\Functional\Domain\Repository;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Utility\Files;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Media\Tests\Functional\AbstractTest;

/**
 * Testcase for an asset repository
 *
 */
class AssetRepositoryTest extends AbstractTest
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->prepareTemporaryDirectory();
        $this->prepareResourceManager();

        $this->assetRepository = $this->objectManager->get(AssetRepository::class);
        $this->assetCollectionRepository = $this->objectManager->get(AssetCollectionRepository::class);
        $this->tagRepository = $this->objectManager->get(TagRepository::class);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        Files::removeDirectoryRecursively($this->temporaryDirectory);
    }

    /**
     * @test
     */
    public function assetsCanBePersisted()
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);

        $this->assetRepository->add($asset);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        self::assertCount(1, $this->assetRepository->findAll());
        self::assertInstanceOf(Asset::class, $this->assetRepository->findAll()->getFirst());

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }

    /**
     * @test
     */
    public function findBySearchTermReturnsFilteredResult()
    {
        $resource1 = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/license.txt');
        $resource2 = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/417px-Mihaly_Csikszentmihalyi.jpg');

        $asset1 = new Asset($resource1);
        $asset1->setTitle('foo bar');
        $asset2 = new Asset($resource2);
        $asset2->setTitle('foobar');

        $this->assetRepository->add($asset1);
        $this->assetRepository->add($asset2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        self::assertCount(2, $this->assetRepository->findAll());
        self::assertCount(2, $this->assetRepository->findBySearchTermOrTags('foo'));
        self::assertCount(1, $this->assetRepository->findBySearchTermOrTags(' bar'));
        self::assertCount(0, $this->assetRepository->findBySearchTermOrTags('baz'));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }

    /**
     * @test
     */
    public function findBySearchTermAndTagsReturnsFilteredResult()
    {
        $tag = new Tag('home');
        $this->tagRepository->add($tag);

        $resource1 = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/license.txt');
        $resource2 = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/417px-Mihaly_Csikszentmihalyi.jpg');
        $asset1 = new Asset($resource1);
        $asset1->setTitle('asset for homepage');
        $asset2 = new Asset($resource2);
        $asset2->setTitle('just another asset');
        $asset2->addTag($tag);

        $this->assetRepository->add($asset1);
        $this->assetRepository->add($asset2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        self::assertCount(2, $this->assetRepository->findBySearchTermOrTags('home', [$tag]));
        self::assertCount(2, $this->assetRepository->findBySearchTermOrTags('homepage', [$tag]));
        self::assertCount(1, $this->assetRepository->findBySearchTermOrTags('baz', [$tag]));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }

    /**
     * @test
     */
    public function testAddAssetVariantFilterClauseWithoutAssetCollection()
    {
        $resource1 = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/417px-Mihaly_Csikszentmihalyi.jpg');
        $resource2 = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/640px-Goodworkteam.jpg');

        $image1 = new Image($resource1);
        $image1->setTitle('asset for homepage');
        $this->assetRepository->add($image1);

        $imageVariant1 = new ImageVariant($image1);
        $image1->addVariant($imageVariant1);

        $image2 = new Image($resource2);
        $image2->setTitle('asset for homepage');
        $this->assetRepository->add($image2);

        $imageVariant2 = new ImageVariant($image2);
        $image2->addVariant($imageVariant2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $assets = $this->assetRepository->findAll();

        self::assertCount(2, $assets);
        foreach ($assets as $asset) {
            self::assertInstanceOf(Image::class, $asset);
            self::assertNotInstanceOf(ImageVariant::class, $asset);
        }

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }

    /**
     * @test
     */
    public function testAddAssetVariantFilterClauseWithAssetCollection()
    {
        $resource1 = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/417px-Mihaly_Csikszentmihalyi.jpg');
        $resource2 = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/640px-Goodworkteam.jpg');

        $assetCollection1 = new AssetCollection('test-1');
        $this->assetCollectionRepository->add($assetCollection1);

        $collections1 = new ArrayCollection();
        $collections1->add($assetCollection1);

        $image1 = new Image($resource1);
        $image1->setTitle('asset for homepage');
        $image1->setAssetCollections($collections1);
        $assetCollection1->addAsset($image1);

        $imageVariant1 = new ImageVariant($image1);
        $image1->addVariant($imageVariant1);

        $assetCollection2 = new AssetCollection('test-2');
        $this->assetCollectionRepository->add($assetCollection2);

        $collections2 = new ArrayCollection();
        $collections2->add($assetCollection2);

        $image2 = new Image($resource2);
        $image2->setTitle('asset for homepage');
        $image2->setAssetCollections($collections2);
        $assetCollection2->addAsset($image2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $assets = $this->assetRepository->findAll($assetCollection1);
        self::assertCount(1, $assets);
        self::assertInstanceOf(Image::class, $assets->getFirst());
        self::assertNotInstanceOf(ImageVariant::class, $assets->getFirst());
        self::assertNotInstanceOf(ImageVariant::class, $assets->getFirst());

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }
}
