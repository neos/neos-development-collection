<?php
namespace TYPO3\Media\Tests\Functional\Domain\Repository;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Media\Domain\Model\Tag;

/**
 * Testcase for an asset repository
 *
 */
class AssetRepositoryTest extends \TYPO3\Media\Tests\Functional\AbstractTest
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \TYPO3\Media\Domain\Repository\AssetRepository
     */
    protected $assetRepository;

    /**
     * @var \TYPO3\Media\Domain\Repository\TagRepository
     */
    protected $tagRepository;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->prepareTemporaryDirectory();
        $this->prepareResourceManager();

        $this->assetRepository = $this->objectManager->get('TYPO3\Media\Domain\Repository\AssetRepository');
        $this->tagRepository = $this->objectManager->get('TYPO3\Media\Domain\Repository\TagRepository');
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        \TYPO3\Flow\Utility\Files::removeDirectoryRecursively($this->temporaryDirectory);
    }

    /**
     * @test
     */
    public function assetsCanBePersisted()
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/license.txt');
        $asset = new \TYPO3\Media\Domain\Model\Asset($resource);

        $this->assetRepository->add($asset);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->assertCount(1, $this->assetRepository->findAll());
        $this->assertInstanceOf('TYPO3\Media\Domain\Model\Asset', $this->assetRepository->findAll()->getFirst());

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

        $asset1 = new \TYPO3\Media\Domain\Model\Asset($resource1);
        $asset1->setTitle('foo bar');
        $asset2 = new \TYPO3\Media\Domain\Model\Asset($resource2);
        $asset2->setTitle('foobar');

        $this->assetRepository->add($asset1);
        $this->assetRepository->add($asset2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->assertCount(2, $this->assetRepository->findAll());
        $this->assertCount(2, $this->assetRepository->findBySearchTermOrTags('foo'));
        $this->assertCount(1, $this->assetRepository->findBySearchTermOrTags(' bar'));
        $this->assertCount(0, $this->assetRepository->findBySearchTermOrTags('baz'));

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
        $asset1 = new \TYPO3\Media\Domain\Model\Asset($resource1);
        $asset1->setTitle('asset for homepage');
        $asset2 = new \TYPO3\Media\Domain\Model\Asset($resource2);
        $asset2->setTitle('just another asset');
        $asset2->addTag($tag);

        $this->assetRepository->add($asset1);
        $this->assetRepository->add($asset2);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->assertCount(2, $this->assetRepository->findBySearchTermOrTags('home', array($tag)));
        $this->assertCount(2, $this->assetRepository->findBySearchTermOrTags('homepage', array($tag)));
        $this->assertCount(1, $this->assetRepository->findBySearchTermOrTags('baz', array($tag)));

        // This is necessary to initialize all resource instances before the tables are deleted
        foreach ($this->assetRepository->findAll() as $asset) {
            $asset->getResource()->getSha1();
        }
    }
}
