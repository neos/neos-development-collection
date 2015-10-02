<?php
namespace TYPO3\Media\Tests\Unit\Domain\Model;

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
use TYPO3\Media\Domain\Model\ImageVariant;

/**
 * Testcase for an image variant
 *
 */
class ImageVariantTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function constructorSetsPropertiesCorrectly()
    {
        $imageMock = $this->getImageMock();
        $variant = new ImageVariant($imageMock, array('foo'), 'dummyAlias');
        $this->assertSame($imageMock, $variant->getOriginalImage());
        $this->assertSame(array('foo'), $variant->getProcessingInstructions());
        $this->assertSame('dummyAlias', $variant->getAlias());
    }

    /**
     * @test
     */
    public function getThumbnailLeavesPresentProcessingInstructionsInPlace()
    {
        $imageMock = $this->getImageMock();

        $variant = new ImageVariant($imageMock, array('somePreset' => array('processing' => 'instructions')), 'dummyAlias');
        $thumbnailVariant = $variant->getThumbnail();
        $actualProcessingInstructions = $thumbnailVariant->getProcessingInstructions();
        $this->assertArrayHasKey('somePreset', $actualProcessingInstructions);
        $this->assertEquals(array('processing' => 'instructions'), $actualProcessingInstructions['somePreset']);
    }

    /**
     * @return Image|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getImageMock()
    {
        $mockResource = $this->getMock('TYPO3\Flow\Resource\Resource');
        $mockResource
            ->expects($this->any())
            ->method('getResourcePointer')
            ->will($this->returnValue($this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', false)));

        return $this->getAccessibleMock('TYPO3\Media\Domain\Model\Image', array('initialize'), array('resource' => $mockResource));
    }
}
