<?php

namespace Neos\Media\Tests\Functional\Eel;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Eel\AssetsHelper;
use Neos\Utility\Files;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Media\Tests\Functional\AbstractTest;

/**
 * Testcase for the asset helper
 *
 */
class AssetHelperTest extends AbstractTest
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
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

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
        $this->tagRepository = $this->objectManager->get(TagRepository::class);
        $this->assetCollectionRepository = $this->objectManager->get(AssetCollectionRepository::class);
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
    public function findByTagFindAssetsByTagLabel()
    {
        $fooTag = new Tag('foo');
        $this->tagRepository->add($fooTag);

        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);
        $asset->setTitle('asset for homepage');
        $asset->addTag($fooTag);

        $this->assetRepository->add($asset);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->findByTag('foo'));
        self::assertNull($helper->findByTag('bar'));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }

    /**
     * @test
     */
    public function findByTagFindAssetsByTagInstace()
    {
        $fooTag = new Tag('foo');
        $this->tagRepository->add($fooTag);

        $barTag = new Tag('bar');
        $this->tagRepository->add($barTag);

        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);
        $asset->setTitle('asset for homepage');
        $asset->addTag($fooTag);

        $this->assetRepository->add($asset);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->findByTag($fooTag));
        self::assertCount(0, $helper->findByTag($barTag));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }

    /**
     * @test
     */
    public function findByTagReturnsNullIfTagIsNull()
    {
        $helper = new AssetsHelper();
        self::assertNull($helper->findByTag(null));
    }

    /**
     * @test
     */
    public function findByCollectionReturnsAssetsForCollectionLabel()
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);
        $asset->setTitle('asset for foo');
        $this->assetRepository->add($asset);

        $fooCollection = new AssetCollection('foo');
        $barCollection = new AssetCollection('bar');
        $fooCollection->addAsset($asset);
        $this->assetCollectionRepository->add($fooCollection);
        $this->assetCollectionRepository->add($barCollection);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->findByCollection('foo'));
        self::assertCount(0, $helper->findByCollection('bar'));
        self::assertNull($helper->findByCollection(''));
    }

    /**
     * @test
     */
    public function findByCollectionReturnsAssetsForCollectionInstace()
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);
        $asset->setTitle('asset for foo');
        $this->assetRepository->add($asset);

        $fooCollection = new AssetCollection('foo');
        $barCollection = new AssetCollection('bar');
        $fooCollection->addAsset($asset);
        $this->assetCollectionRepository->add($fooCollection);
        $this->assetCollectionRepository->add($barCollection);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->findByCollection($fooCollection));
        self::assertCount(0, $helper->findByCollection($barCollection));
        self::assertNull($helper->findByCollection(null));
    }

    /**
     * @test
     */
    public function findByCollectionReturnsNullIfCollectionIsNull()
    {
        $helper = new AssetsHelper();
        self::assertNull($helper->findByCollection(null));
        self::assertNull($helper->findByCollection(''));
    }

    /**
     * @test
     */
    public function searchWithoutSearchTermReturnsNull()
    {
        $helper = new AssetsHelper();
        self::assertNull($helper->search(null));
        self::assertNull($helper->search(''));
    }

    /**
     * @test
     */
    public function searchWithSearchTermAndTagFindsAsset()
    {
        $fooTag = new Tag('foo');
        $this->tagRepository->add($fooTag);

        $barTag = new Tag('bar');
        $this->tagRepository->add($barTag);

        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $resource2 = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/417px-Mihaly_Csikszentmihalyi.jpg');
        $asset = new Asset($resource);
        $asset->setTitle('asset for foo');
        $asset->addTag($fooTag);
        $asset2 = new Asset($resource2);
        $asset2->setTitle('Another asset');
        $asset2->addTag($barTag);

        $this->assetRepository->add($asset);
        $this->assetRepository->add($asset2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->search('foo', [$fooTag]));
        self::assertCount(2, $helper->search('asset', [$fooTag]));
        self::assertCount(2, $helper->search('asset'));
        self::assertCount(1, $helper->search('bar', [$barTag]));
        self::assertCount(1, $helper->search('baz', [$barTag]));
        self::assertCount(0, $helper->search('baz'));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }

    /**
     * @test
     */
    public function searchWithSearchTermAndCollectionFindsAsset()
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);
        $asset->setTitle('asset for foo');
        $this->assetRepository->add($asset);

        $fooCollection = new AssetCollection('foo');
        $barCollection = new AssetCollection('bar');
        $fooCollection->addAsset($asset);
        $this->assetCollectionRepository->add($fooCollection);
        $this->assetCollectionRepository->add($barCollection);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->search('foo', [], $fooCollection));
        self::assertCount(1, $helper->search('foo', [], 'foo'));
        self::assertCount(0, $helper->search('foo', [], $barCollection));
        self::assertCount(1, $helper->search('asset', [], null));
        self::assertCount(1, $helper->search('asset'));
        self::assertCount(0, $helper->search('baz'));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }
}
