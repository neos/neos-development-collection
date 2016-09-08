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
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Utility\Files;

/**
 * Abstract Functional Test template
 */
abstract class AbstractTest extends FunctionalTestCase
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
     * @var ResourceManager
     */
    protected $resourceManager;

    public function tearDown()
    {
        $persistenceManager = self::$bootstrap->getObjectManager()->get(PersistenceManagerInterface::class);
        if (is_callable(array($persistenceManager, 'tearDown'))) {
            $persistenceManager->tearDown();
        }
        self::$bootstrap->getObjectManager()->forgetInstance(PersistenceManagerInterface::class);
        parent::tearDown();
    }

    /**
     * Creates an Image object from a file using a mock resource (in order to avoid a database resource pointer entry)
     * @param string $imagePathAndFilename
     * @return Resource
     */
    protected function getMockResourceByImagePath($imagePathAndFilename)
    {
        $imagePathAndFilename = Files::getUnixStylePath($imagePathAndFilename);
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
     * @return Resource
     */
    protected function createMockResourceAndPointerFromHash($hash)
    {
        $mockResource = $this->getMockBuilder(Resource::class)->setMethods(array('getHash', 'getUri'))->getMock();
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
        $this->temporaryDirectory = Files::concatenatePaths(array(FLOW_PATH_DATA, 'Temporary', 'Testing', str_replace('\\', '_', __CLASS__)));
        if (!file_exists($this->temporaryDirectory)) {
            Files::createDirectoryRecursively($this->temporaryDirectory);
        }
    }

    /**
     * Initializes the resource manager and modifies the persistent resource storage location.
     * @return void
     */
    protected function prepareResourceManager()
    {
        $this->resourceManager = $this->objectManager->get(ResourceManager::class);
    }
}
