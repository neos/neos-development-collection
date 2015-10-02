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

/**
 * Testcase for an image repository
 *
 */
class ImageRepositoryTest extends \TYPO3\Media\Tests\Functional\AbstractTest
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \TYPO3\Media\Domain\Repository\ImageRepository
     */
    protected $imageRepository;

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

        $this->imageRepository = $this->objectManager->get('TYPO3\Media\Domain\Repository\ImageRepository');
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $reflectedProperty = new \ReflectionProperty('TYPO3\Flow\Resource\ResourceManager', 'persistentResourcesStorageBaseUri');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue($this->resourceManager, $this->oldPersistentResourcesStorageBaseUri);

        \TYPO3\Flow\Utility\Files::removeDirectoryRecursively($this->temporaryDirectory);
    }

    /**
     * @test
     */
    public function imagesCanBePersisted()
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/640px-Goodworkteam.jpg');
        $image = new \TYPO3\Media\Domain\Model\Image($resource);

        $this->imageRepository->add($image);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $this->assertCount(1, $this->imageRepository->findAll());
        $this->assertInstanceOf('TYPO3\Media\Domain\Model\Image', $this->imageRepository->findAll()->getFirst());
    }

    /**
     * @test
     */
    public function imagesAndTheirVariantsArePersistedCorrectly()
    {
        $resource = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/640px-Goodworkteam.jpg');
        $image = new \TYPO3\Media\Domain\Model\Image($resource);
        $this->imageRepository->add($image);

        $alias = 'testAlias';
        $image->createImageVariant($processingInstructions = array(
            array(
                'command' => 'thumbnail',
                'options' => array(
                    'size' => array(
                        'width' => 50,
                        'height' => 50
                    )
                ),
            ),
        ), $alias);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $image = $this->imageRepository->findAll()->getFirst();
        $this->assertInstanceOf('TYPO3\Media\Domain\Model\Image', $image);
        $this->assertCount(1, $image->getImageVariants());
        $this->assertInstanceOf('TYPO3\Media\Domain\Model\ImageVariant', $image->getImageVariantByAlias($alias));
    }
}
