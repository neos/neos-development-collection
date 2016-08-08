<?php
namespace TYPO3\Media\Tests\Functional\Domain\Model;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Doctrine\Common\Collections\ArrayCollection;
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\Tag;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\Domain\Repository\TagRepository;
use TYPO3\Media\Tests\Functional\AbstractTest;

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
    public function setUp()
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
            $this->assertArrayHasKey($tag->getLabel(), $expectedTagLabels);
            unset($expectedTagLabels[$tag->getLabel()]);
        }

        $this->assertCount(0, $expectedTagLabels);
    }

    /**
     * @return Asset
     * @throws \TYPO3\Flow\Resource\Exception
     */
    protected function buildAssetObject()
    {
        $resource = $this->resourceManager->importResourceFromContent('Test', 'test.txt');
        $asset = new Asset($resource);
        $this->assetRepository->add($asset);
        return $asset;
    }
}
