<?php
namespace TYPO3\Media\Tests\Functional;

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
        $resourcePointer = new \TYPO3\Flow\Resource\ResourcePointer($hash);

        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource', array('getResourcePointer', 'getUri'));
        $mockResource->expects($this->any())
                ->method('getResourcePointer')
                ->will($this->returnValue($resourcePointer));
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

        $reflectedProperty = new \ReflectionProperty('TYPO3\Flow\Resource\ResourceManager', 'persistentResourcesStorageBaseUri');
        $reflectedProperty->setAccessible(true);
        $this->oldPersistentResourcesStorageBaseUri = $reflectedProperty->getValue($this->resourceManager);
        $reflectedProperty->setValue($this->resourceManager, $this->temporaryDirectory . '/');
    }
}
