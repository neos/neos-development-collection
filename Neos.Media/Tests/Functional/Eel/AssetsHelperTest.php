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
class AssetsHelperTest extends AbstractTest
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

    public function tearDown(): void
    {
        parent::tearDown();

        Files::removeDirectoryRecursively($this->temporaryDirectory);
    }

    /**
     * @test
     */
    public function findByTagFindAssetsByTagLabel(): void
    {
        $tagA = new Tag('tagA');
        $this->tagRepository->add($tagA);

        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);
        $asset->setTitle('asset for homepage');
        $asset->addTag($tagA);

        $this->assetRepository->add($asset);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->findByTag('tagA'));
        self::assertNull($helper->findByTag('tagB'));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }

    /**
     * @test
     */
    public function findByTagFindAssetsByTagInstance(): void
    {
        $tagA = new Tag('tagA');
        $this->tagRepository->add($tagA);

        $tagB = new Tag('tagB');
        $this->tagRepository->add($tagB);

        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);
        $asset->setTitle('asset for homepage');
        $asset->addTag($tagA);

        $this->assetRepository->add($asset);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->findByTag($tagA));
        self::assertCount(0, $helper->findByTag($tagB));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }

    /**
     * @test
     */
    public function findByTagReturnsNullIfTagIsNull(): void
    {
        $helper = new AssetsHelper();
        self::assertNull($helper->findByTag(null));
    }

    /**
     * @test
     */
    public function findByCollectionReturnsAssetsForCollectionLabel(): void
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);
        $asset->setTitle('asset for tagA');
        $this->assetRepository->add($asset);

        $tagACollection = new AssetCollection('tagA');
        $tagBCollection = new AssetCollection('tagB');
        $tagACollection->addAsset($asset);
        $this->assetCollectionRepository->add($tagACollection);
        $this->assetCollectionRepository->add($tagBCollection);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->findByCollection('tagA'));
        self::assertCount(0, $helper->findByCollection('tagB'));
        self::assertNull($helper->findByCollection(''));
    }

    /**
     * @test
     */
    public function findByCollectionReturnsAssetsForCollectionInstace(): void
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);
        $asset->setTitle('asset for tagA');
        $this->assetRepository->add($asset);

        $tagACollection = new AssetCollection('tagA');
        $tagBCollection = new AssetCollection('tagB');
        $tagACollection->addAsset($asset);
        $this->assetCollectionRepository->add($tagACollection);
        $this->assetCollectionRepository->add($tagBCollection);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->findByCollection($tagACollection));
        self::assertCount(0, $helper->findByCollection($tagBCollection));
        self::assertNull($helper->findByCollection(null));
    }

    /**
     * @test
     */
    public function findByCollectionReturnsNullIfCollectionIsNull(): void
    {
        $helper = new AssetsHelper();
        self::assertNull($helper->findByCollection(null));
        self::assertNull($helper->findByCollection(''));
    }

    /**
     * @test
     */
    public function searchWithoutSearchTermReturnsNull(): void
    {
        $helper = new AssetsHelper();
        self::assertNull($helper->search(null));
        self::assertNull($helper->search(''));
    }

    /**
     * @test
     */
    public function searchWithSearchTermAndTagFindsAsset(): void
    {
        $tagA = new Tag('tagA');
        $this->tagRepository->add($tagA);

        $tagB = new Tag('tagB');
        $this->tagRepository->add($tagB);

        $tagC = new Tag('tagC');
        $this->tagRepository->add($tagC);

        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $resource2 = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/417px-Mihaly_Csikszentmihalyi.jpg');
        $asset = new Asset($resource);
        $asset->setTitle('asset for tagA');
        $asset->addTag($tagA);
        $asset2 = new Asset($resource2);
        $asset2->setTitle('Another asset');
        $asset2->addTag($tagB);

        $this->assetRepository->add($asset);
        $this->assetRepository->add($asset2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->search('tagA', [$tagA]));
        self::assertCount(2, $helper->search('asset', [$tagA->getLabel()]));
        self::assertCount(2, $helper->search('asset'));
        self::assertCount(1, $helper->search('tagB', [$tagB]));
        self::assertCount(1, $helper->search('tagB', [$tagB->getLabel()]));
        self::assertCount(0, $helper->search('tagD'));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }

    /**
     * @test
     */
    public function searchWithSearchTermAndCollectionFindsAsset(): void
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/license.txt');
        $asset = new Asset($resource);
        $asset->setTitle('asset for tagA');
        $this->assetRepository->add($asset);

        $tagACollection = new AssetCollection('tagA');
        $tagBCollection = new AssetCollection('tagB');
        $tagACollection->addAsset($asset);
        $this->assetCollectionRepository->add($tagACollection);
        $this->assetCollectionRepository->add($tagBCollection);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $helper = new AssetsHelper();
        self::assertCount(1, $helper->search('tagA', [], $tagACollection));
        self::assertCount(1, $helper->search('tagA', [], 'tagA'));
        self::assertCount(0, $helper->search('tagA', [], $tagBCollection));
        self::assertCount(1, $helper->search('asset', [], null));
        self::assertCount(1, $helper->search('asset'));
        self::assertCount(0, $helper->search('tagC'));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }
}
