<?php
namespace TYPO3\Media\Tests\Functional\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Media\Domain\Model\Image;

/**
 * Functional Test case for the ImageConverter
 */
class ImageConverterTest extends \TYPO3\Media\Tests\Functional\AbstractTest {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @var \TYPO3\Media\Domain\Repository\ImageRepository
	 */
	protected $imageRepository;

	/**
	 */
	public function setUp() {
		parent::setUp();
		$this->propertyMapper = $this->objectManager->get('TYPO3\Flow\Property\PropertyMapper');
		$this->resourceManager = $this->objectManager->get('TYPO3\Flow\Resource\ResourceManager');
		$this->imageRepository = $this->objectManager->get('TYPO3\Media\Domain\Repository\ImageRepository');
	}

	/**
	 */
	public function tearDown() {
		foreach ($this->resourceManager->getImportedResources() AS $resource) {
			$this->resourceManager->deleteResource($resource);
		}
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function mappingPlainArrayToImageInstanceProvidingResourceOnlyWorks() {
		$mockResource = $this->getMockResourceByImagePath(__DIR__ . '/../Fixtures/Resources/640px-Goodworkteam.jpg');

		$source = array('resource' => $mockResource);
		$mappedImage = $this->propertyMapper->convert($source, 'TYPO3\Media\Domain\Model\Image');
		$this->assertInstanceOf('TYPO3\Media\Domain\Model\Image', $mappedImage);
	}

	/**
	 * @test
	 */
	public function mappingPlainArrayToImageInstanceProvidingResourceAndTitleWorks() {
		$mockResource = $this->getMockResourceByImagePath(__DIR__ . '/../Fixtures/Resources/640px-Goodworkteam.jpg');

		$source = array('resource' => $mockResource, 'title' =>'Some dummy title');
		$mappedImage = $this->propertyMapper->convert($source, 'TYPO3\Media\Domain\Model\Image');
		$this->assertInstanceOf('TYPO3\Media\Domain\Model\Image', $mappedImage);
		$this->assertSame('Some dummy title', $mappedImage->getTitle());
	}

	/**
	 * @test
	 */
	public function mappingPreviouslyPersistedImageWorks() {
		$image = $this->createAndPersistExampleImage();
		$imageIdentifier = $this->persistenceManager->getIdentifierByObject($image);

		$resurrectedImage = $this->imageRepository->findByIdentifier($imageIdentifier);
		$this->assertInstanceOf('TYPO3\Media\Domain\Model\Image', $resurrectedImage);

		$source = array('__identity' => $imageIdentifier);
		$mappedImage = $this->propertyMapper->convert($source, 'TYPO3\Media\Domain\Model\Image');
		$this->assertSame($resurrectedImage, $mappedImage);
		$this->assertSame('The original title of the image.', $mappedImage->getTitle());
	}

	/**
	 * @test
	 */
	public function mappingPreviouslyPersistedImageWithTitleChangingWorks() {
		$image = $this->createAndPersistExampleImage();
		$imageIdentifier = $this->persistenceManager->getIdentifierByObject($image);

		$source = array('__identity' => $imageIdentifier, 'title' => 'a changed title');
		$mappedImage = $this->propertyMapper->convert($source, 'TYPO3\Media\Domain\Model\Image');
		$this->assertSame('a changed title', $mappedImage->getTitle());
	}

	/**
	 * @test
	 */
	public function mappingPreviouslyPersistedImageWithTitleChangingAndPersistingAgainWorks() {
		$image = $this->createAndPersistExampleImage();
		$imageIdentifier = $this->persistenceManager->getIdentifierByObject($image);

		$source = array('__identity' => $imageIdentifier, 'title' => 'a changed title which should stay through persistence');
		$mappedImage = $this->propertyMapper->convert($source, 'TYPO3\Media\Domain\Model\Image');
		$this->imageRepository->update($mappedImage);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$resurrectedImage = $this->imageRepository->findByIdentifier($imageIdentifier);
		$this->assertSame('a changed title which should stay through persistence', $resurrectedImage->getTitle());
	}

	/**
	 * @return \TYPO3\Media\Domain\Model\Image
	 */
	protected function createAndPersistExampleImage() {
		$resource = $this->resourceManager->importResource(__DIR__ . '/../Fixtures/Resources/640px-Goodworkteam.jpg');
		$image = new Image($resource);
		$image->setTitle('The original title of the image.');
		$this->imageRepository->add($image);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
		return $image;
	}

}
