<?php
namespace TYPO3\Media\Tests\Unit\Domain\Model;

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
