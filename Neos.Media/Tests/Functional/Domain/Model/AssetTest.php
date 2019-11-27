<?php
declare(strict_types=1);

namespace Neos\Media\Tests\Functional\Domain\Model;

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
use Neos\Flow\Persistence\Repository;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Media\Tests\Functional\AbstractTest;

/**
 * Testcase for an asset model
 */
class AssetTest extends AbstractTest
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
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }

        $this->resourceManager = $this->objectManager->get(ResourceManager::class);
        $this->assetRepository = $this->objectManager->get(AssetRepository::class);
        $this->tagRepository = $this->objectManager->get(TagRepository::class);
    }

    /**
     * @test
     */
    public function setTags()
    {
        $tagLabels = ['foo', 'bar'];

        $tagCollection = new ArrayCollection();

        foreach ($tagLabels as $tagLabel) {
            $tag = new Tag($tagLabel);
            $this->tagRepository->add($tag);
            $tagCollection->add($tag);
        }

        $asset = $this->buildAssetObject();
        $asset->setTags($tagCollection);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $asset = $this->assetRepository->findAll()->getFirst();
        $this->assertAssetHasTags($asset, $tagLabels);
    }

    /**
     * @test
     */
    public function addTag()
    {
        $asset = $this->buildAssetObject();
        $tag = new Tag('test');
        $this->tagRepository->add($tag);
        $asset->addTag($tag);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $asset = $this->assetRepository->findAll()->getFirst();
        $this->assertAssetHasTags($asset, ['test']);
    }


    /**
     * @param Asset $asset
     * @param $tagLabels
     */
    protected function assertAssetHasTags(Asset $asset, $tagLabels)
    {
        $tags = $asset->getTags();
        $tagLabels = array_combine(array_values($tagLabels), array_values($tagLabels));

        $expectedTagLabels = $tagLabels;
        foreach ($tags as $tag) {
            self::assertArrayHasKey($tag->getLabel(), $expectedTagLabels);
            unset($expectedTagLabels[$tag->getLabel()]);
        }

        self::assertCount(0, $expectedTagLabels);
    }

    /**
     * @test
     */
    public function getAssetProxyReturnsNullIfAssetSourceIdentifierPointsToNonExistingAssetSource()
    {
        $asset = $this->buildAssetObject();
        $asset->setAssetSourceIdentifier('non-existing-asset-source');
        self::assertNull($asset->getAssetProxy());
    }

    /**
     * @test
     */
    public function getAssetProxyReturnsNullIfNoCorrespondingImportedAssetExists()
    {
        $asset = $this->buildAssetObject();
        $mockImportedAssetRepository = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()->setMethods(['findOneByLocalAssetIdentifier'])->getMock();
        $this->inject($asset, 'importedAssetRepository', $mockImportedAssetRepository);

        $mockImportedAssetRepository->expects(self::atLeastOnce())->method('findOneByLocalAssetIdentifier')->with($asset->getIdentifier())->willReturn(null);
        self::assertNull($asset->getAssetProxy());
    }

    /**
     * @return Asset
     * @throws \Neos\Flow\ResourceManagement\Exception
     */
    protected function buildAssetObject()
    {
        $resource = $this->resourceManager->importResourceFromContent('Test', 'test.txt');
        $asset = new Asset($resource);
        $this->assetRepository->add($asset);
        return $asset;
    }
}
