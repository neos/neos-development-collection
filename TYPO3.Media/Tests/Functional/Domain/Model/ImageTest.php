<?php
namespace TYPO3\Media\Tests\Functional\Domain\Model;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Media\Domain\Model\Image;

/**
 * Testcase for an image
 *
 */
class ImageTest extends \TYPO3\Media\Tests\Functional\AbstractTest
{
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
    public function setUp()
    {
        parent::setUp();
        $this->prepareTemporaryDirectory();
        $this->prepareResourceManager();

        $this->landscapeImage = new Image($this->getMockResourceByImagePath(__DIR__ . '/../../Fixtures/Resources/640px-Goodworkteam.jpg'));
        $this->portraitImage = new Image($this->getMockResourceByImagePath(__DIR__ . '/../../Fixtures/Resources/417px-Mihaly_Csikszentmihalyi.jpg'));
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
    public function imagesHaveCorrectSize()
    {
        $this->assertEquals(417, $this->portraitImage->getWidth());
        $this->assertEquals(480, $this->portraitImage->getHeight());

        $this->assertEquals(640, $this->landscapeImage->getWidth());
        $this->assertEquals(352, $this->landscapeImage->getHeight());
    }

    /**
     * @test
     */
    public function thumbnailIsGeneratedCorrectlyUsingDefaultRatioModeWhichShouldBeInset()
    {
        $imageVariant = $this->landscapeImage->getThumbnail(50, 50);
        $this->assertEquals(50, $imageVariant->getWidth());
        $this->assertEquals(28, $imageVariant->getHeight());
    }

    /**
     * @test
     */
    public function thumbnailIsGeneratedCorrectlyUsingInsetRatioMode()
    {
        $imageVariant = $this->landscapeImage->getThumbnail(50, 50, \TYPO3\Media\Domain\Model\ImageInterface::RATIOMODE_INSET);
        $this->assertEquals(50, $imageVariant->getWidth());
        $this->assertEquals(28, $imageVariant->getHeight());
    }

    /**
     * @test
     */
    public function thumbnailIsGeneratedCorrectlyUsingOutboundRatioMode()
    {
        $imageVariant = $this->landscapeImage->getThumbnail(50, 50, \TYPO3\Media\Domain\Model\ImageInterface::RATIOMODE_OUTBOUND);
        $this->assertEquals(50, $imageVariant->getWidth());
        $this->assertEquals(50, $imageVariant->getHeight());
    }

    /**
     * @test
     * @expectedException \TYPO3\Media\Exception\ImageFileException
     */
    public function constructingImageFromANonImageResourceThrowsException()
    {
        $dummyImageContent = 'not an actual image';
        $hash = sha1($dummyImageContent);
        file_put_contents('resource://' . $hash, $dummyImageContent);
        $mockResource = $this->createMockResourceAndPointerFromHash($hash);
        $image = new \TYPO3\Media\Domain\Model\Image($mockResource);
        $image->initializeImageSizeAndType();
    }
}
