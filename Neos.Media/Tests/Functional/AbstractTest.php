<?php
namespace Neos\Media\Tests\Functional;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\Files;

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
     * @var ResourceManager
     */
    protected $resourceManager;

    public function tearDown(): void
    {
        $persistenceManager = self::$bootstrap->getObjectManager()->get(PersistenceManagerInterface::class);
        if (is_callable([$persistenceManager, 'tearDown'])) {
            $persistenceManager->tearDown();
        }
        self::$bootstrap->getObjectManager()->forgetInstance(PersistenceManagerInterface::class);
        parent::tearDown();
    }

    /**
     * Creates an Image object from a file using a mock resource (in order to avoid a database resource pointer entry)
     * @param string $imagePathAndFilename
     * @return PersistentResource
     */
    protected function getMockResourceByImagePath($imagePathAndFilename)
    {
        $imagePathAndFilename = Files::getUnixStylePath($imagePathAndFilename);
        $hash = sha1_file($imagePathAndFilename);
        copy($imagePathAndFilename, 'resource://' . $hash);
        return $mockResource = $this->createMockResourceAndPointerFromHash($hash);
    }

    /**
     * Creates a mock ResourcePointer and PersistentResource from a given hash.
     * Make sure that a file representation already exists, e.g. with
     * file_put_content('resource://' . $hash) before
     *
     * @param string $hash
     * @return PersistentResource
     */
    protected function createMockResourceAndPointerFromHash($hash)
    {
        $mockResource = $this->getMockBuilder(PersistentResource::class)->setMethods(['getHash', 'getUri'])->getMock();
        $mockResource->expects(self::any())
                ->method('getHash')
                ->will(self::returnValue($hash));
        $mockResource->expects(self::any())
            ->method('getUri')
            ->will(self::returnValue('resource://' . $hash));
        return $mockResource;
    }

    /**
     * Builds a temporary directory to work on.
     * @return void
     */
    protected function prepareTemporaryDirectory()
    {
        $this->temporaryDirectory = Files::concatenatePaths([FLOW_PATH_DATA, 'Temporary', 'Testing', str_replace('\\', '_', __CLASS__)]);
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
