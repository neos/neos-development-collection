<?php
namespace TYPO3\Media\Tests\Functional;

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
 * Abstract Functional Test template
 */
abstract class AbstractTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
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
     * @var \TYPO3\Flow\Resource\ResourceManager
     */
    protected $resourceManager;

    public function tearDown()
    {
        $persistenceManager = self::$bootstrap->getObjectManager()->get('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        if (is_callable(array($persistenceManager, 'tearDown'))) {
            $persistenceManager->tearDown();
        }
        self::$bootstrap->getObjectManager()->forgetInstance('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        parent::tearDown();
    }

    /**
     * Creates an Image object from a file using a mock resource (in order to avoid a database resource pointer entry)
     * @param string $imagePathAndFilename
     * @return \TYPO3\Flow\Resource\Resource
     */
    protected function getMockResourceByImagePath($imagePathAndFilename)
    {
        $imagePathAndFilename = \TYPO3\Flow\Utility\Files::getUnixStylePath($imagePathAndFilename);
        $hash = sha1_file($imagePathAndFilename);
        copy($imagePathAndFilename, 'resource://' . $hash);
        return $mockResource = $this->createMockResourceAndPointerFromHash($hash);
    }

    /**
     * Creates a mock ResourcePointer and Resource from a given hash.
     * Make sure that a file representation already exists, e.g. with
     * file_put_content('resource://' . $hash) before
     *
     * @param string $hash
     * @return \TYPO3\Flow\Resource\Resource
     */
    protected function createMockResourceAndPointerFromHash($hash)
    {
        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array('getHash', 'getUri'));
        $mockResource->expects($this->any())
                ->method('getHash')
                ->will($this->returnValue($hash));
        $mockResource->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue('resource://' . $hash));
        return $mockResource;
    }

    /**
     * Builds a temporary directory to work on.
     * @return void
     */
    protected function prepareTemporaryDirectory()
    {
        $this->temporaryDirectory = \TYPO3\Flow\Utility\Files::concatenatePaths(array(FLOW_PATH_DATA, 'Temporary', 'Testing', str_replace('\\', '_', __CLASS__)));
        if (!file_exists($this->temporaryDirectory)) {
            \TYPO3\Flow\Utility\Files::createDirectoryRecursively($this->temporaryDirectory);
        }
    }

    /**
     * Initializes the resource manager and modifies the persistent resource storage location.
     * @return void
     */
    protected function prepareResourceManager()
    {
        $this->resourceManager = $this->objectManager->get('TYPO3\Flow\Resource\ResourceManager');
    }
}
