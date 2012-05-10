<?php
namespace TYPO3\Media\Tests\Functional\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Media".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for an image
 *
 */
class ImageTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var string
	 */
	protected $temporaryDirectory;

	/**
	 * @var string
	 * @see prepareResourceManager()
	 */
	protected $oldPersistentResourcesStorageBaseUri;

	/**
	 * @var \TYPO3\FLOW3\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @var \TYPO3\Media\Domain\Model\Image
	 */
	protected $landscapeImage;

	/**
	 * @var \TYPO3\Media\Domain\Model\Image
	 */
	protected $portraitImage;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->prepareTemporaryDirectory();
		$this->prepareResourceManager();

		$this->landscapeImage = $this->getImageFromFileUsingMockResource(__DIR__ . '/../../Fixtures/Resources/640px-Goodworkteam.jpg');
		$this->portraitImage = $this->getImageFromFileUsingMockResource(__DIR__ . '/../../Fixtures/Resources/417px-Mihaly_Csikszentmihalyi.jpg');

	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$reflectedProperty = new \ReflectionProperty('TYPO3\FLOW3\Resource\ResourceManager', 'persistentResourcesStorageBaseUri');
		$reflectedProperty->setAccessible(TRUE);
		$reflectedProperty->setValue($this->resourceManager, $this->oldPersistentResourcesStorageBaseUri);

		\TYPO3\FLOW3\Utility\Files::removeDirectoryRecursively($this->temporaryDirectory);
	}

	/**
	 * @test
	 */
	public function imagesHaveCorrectSize() {
		$this->assertEquals(417, $this->portraitImage->getWidth());
		$this->assertEquals(480, $this->portraitImage->getHeight());

		$this->assertEquals(640, $this->landscapeImage->getWidth());
		$this->assertEquals(352, $this->landscapeImage->getHeight());
	}

	/**
	 * @test
	 */
	public function thumbnailIsGeneratedCorrectlyUsingDefaultRatioModeWhichShouldBeInset() {
		$imageVariant = $this->landscapeImage->getThumbnail(50, 50);
		$this->assertEquals(50, $imageVariant->getWidth());
		$this->assertEquals(28, $imageVariant->getHeight());
	}

	/**
	 * @test
	 */
	public function thumbnailIsGeneratedCorrectlyUsingInsetRatioMode() {
		$imageVariant = $this->landscapeImage->getThumbnail(50, 50, \TYPO3\Media\Domain\Model\ImageInterface::RATIOMODE_INSET);
		$this->assertEquals(50, $imageVariant->getWidth());
		$this->assertEquals(28, $imageVariant->getHeight());
	}

	/**
	 * @test
	 */
	public function thumbnailIsGeneratedCorrectlyUsingOutboundRatioMode() {
		$imageVariant = $this->landscapeImage->getThumbnail(50, 50, \TYPO3\Media\Domain\Model\ImageInterface::RATIOMODE_OUTBOUND);
		$this->assertEquals(50, $imageVariant->getWidth());
		$this->assertEquals(50, $imageVariant->getHeight());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Media\Exception\ImageFileException
	 */
	public function constructingImageFromANonImageResourceThrowsException() {
		$dummyImageContent = 'not an actual image';
		$hash = sha1($dummyImageContent);
		file_put_contents('resource://' . $hash, $dummyImageContent);
		$mockResource = $this->createMockResourceAndPointerFromHash($hash);
		new \TYPO3\Media\Domain\Model\Image($mockResource);
	}


	/**
	 * Creates an Image object from a file using a mock resource (in order to avoid a database resource pointer entry)
	 * @param string $imagePathAndFilename
	 * @return \TYPO3\Media\Domain\Model\Image
	 */
	protected function getImageFromFileUsingMockResource($imagePathAndFilename) {
		$imagePathAndFilename = \TYPO3\FLOW3\Utility\Files::getUnixStylePath($imagePathAndFilename);
		$hash = sha1_file($imagePathAndFilename);
		copy($imagePathAndFilename, 'resource://' . $hash);
		$mockResource = $this->createMockResourceAndPointerFromHash($hash);

		return new \TYPO3\Media\Domain\Model\Image($mockResource);
	}

	/**
	 * Creates a mock ResourcePointer and Resource from a given hash.
	 * Make sure that a file representation already exists, e.g. with
	 * file_put_content('resource://' . $hash) before
	 *
	 * @param string $hash
	 * @return \TYPO3\FLOW3\Resource\Resource
	 */
	protected function createMockResourceAndPointerFromHash($hash) {
		$resourcePointer = new \TYPO3\FLOW3\Resource\ResourcePointer($hash);

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array('getResourcePointer'));
		$mockResource->expects($this->any())
				->method('getResourcePointer')
				->will($this->returnValue($resourcePointer));
		return $mockResource;
	}

	/**
	 * Builds a temporary directory to work on.
	 * @return void
	 */
	protected function prepareTemporaryDirectory() {
		$this->temporaryDirectory = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(realpath(sys_get_temp_dir()), str_replace('\\', '_', __CLASS__)));
		if (!file_exists($this->temporaryDirectory)) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->temporaryDirectory);
		}
	}

	/**
	 * Initializes the resource manager and modifies the persistent resource storage location.
	 * @return void
	 */
	protected function prepareResourceManager() {
		$this->resourceManager = $this->objectManager->get('TYPO3\FLOW3\Resource\ResourceManager');

		$reflectedProperty = new \ReflectionProperty('TYPO3\FLOW3\Resource\ResourceManager', 'persistentResourcesStorageBaseUri');
		$reflectedProperty->setAccessible(TRUE);
		$this->oldPersistentResourcesStorageBaseUri = $reflectedProperty->getValue($this->resourceManager);
		$reflectedProperty->setValue($this->resourceManager, $this->temporaryDirectory . '/');
	}

}
?>