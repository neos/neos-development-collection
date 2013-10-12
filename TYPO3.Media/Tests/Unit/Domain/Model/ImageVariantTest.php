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

/**
 * Testcase for an image variant
 *
 */
class ImageVariantTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function constructorSetsPropertiesCorrectly() {
		$imageMock = $this->getImageMock();
		$variant = new \TYPO3\Media\Domain\Model\ImageVariant($imageMock, array('foo'), 'dummyAlias');
		$this->assertSame($imageMock, $variant->getOriginalImage());
		$this->assertSame(array('foo'), $variant->getProcessingInstructions());
		$this->assertSame('dummyAlias', $variant->getAlias());
	}

	/**
	 * @return \TYPO3\Media\Domain\Model\Image
	 */
	protected function getImageMock() {
		$mockResource = $this->getMock('TYPO3\Flow\Resource\Resource');
		$mockResource
			->expects($this->any())
			->method('getResourcePointer')
			->will($this->returnValue($this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', FALSE)));

		return $this->getAccessibleMock('TYPO3\Media\Domain\Model\Image', array('initialize'), array('resource' => $mockResource));
	}

}
