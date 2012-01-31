<?php
namespace TYPO3\Media\Tests\Functional\Domain\Repository;

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
 * Testcase for an image repository
 *
 */
class ImageRepositoryTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

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
		 * @var boolean
		 */
		static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\FLOW3\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
		$this->prepareTemporaryDirectory();
		$this->prepareResourceManager();
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		$reflectedProperty = new \ReflectionProperty('TYPO3\FLOW3\Resource\ResourceManager', 'persistentResourcesStorageBaseUri');
		$reflectedProperty->setAccessible(TRUE);
		$reflectedProperty->setValue($this->resourceManager, $this->oldPersistentResourcesStorageBaseUri);

		\TYPO3\FLOW3\Utility\Files::removeDirectoryRecursively($this->temporaryDirectory);
	}

	/**
	 * @test
	 */
	public function imagesCanBePersisted() {
		$imagePathAndFilename = \TYPO3\FLOW3\Utility\Files::getUnixStylePath(__DIR__ . '/../../Fixtures/Resources/640px-Goodworkteam.jpg');
		$hash = sha1_file($imagePathAndFilename);
		copy($imagePathAndFilename, 'resource://' . $hash);
		$resource = new \TYPO3\FLOW3\Resource\Resource();
		$resource->setResourcePointer(new \TYPO3\FLOW3\Resource\ResourcePointer($hash));
		$image = new \TYPO3\Media\Domain\Model\Image($resource);
		$image->setTitle('');

		$imageRepository = new \TYPO3\Media\Domain\Repository\ImageRepository();
		$imageRepository->add($image);
		$this->persistenceManager->persistAll();
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