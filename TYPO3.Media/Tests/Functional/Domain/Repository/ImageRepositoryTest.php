<?php
namespace TYPO3\Media\Tests\Functional\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for an image repository
 *
 */
class ImageRepositoryTest extends \TYPO3\Media\Tests\Functional\AbstractTest {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\Media\Domain\Repository\ImageRepository
	 */
	protected $imageRepository;

	/**
	 * @return void
	 */
	public function setUp() {
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
	public function tearDown() {
		parent::tearDown();
		$reflectedProperty = new \ReflectionProperty('TYPO3\Flow\Resource\ResourceManager', 'persistentResourcesStorageBaseUri');
		$reflectedProperty->setAccessible(TRUE);
		$reflectedProperty->setValue($this->resourceManager, $this->oldPersistentResourcesStorageBaseUri);

		\TYPO3\Flow\Utility\Files::removeDirectoryRecursively($this->temporaryDirectory);
	}

	/**
	 * @test
	 */
	public function imagesCanBePersisted() {
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
	public function imagesAndTheirVariantsArePersistedCorrectly() {
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
