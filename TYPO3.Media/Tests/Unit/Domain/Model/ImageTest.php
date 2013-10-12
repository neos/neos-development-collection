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
 * Testcase for an image
 *
 */
class ImageTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Media\Domain\Model\Image
	 */
	protected $image;

	/**
	 * @return void
	 */
	public function setUp() {
		$mockResourcePointer = $this->getMock('TYPO3\Flow\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer
			->expects($this->any())
			->method('getHash')
			->will($this->returnValue('dummyResourcePointerHash'));

		$mockResource = $this->getMock('TYPO3\Flow\Resource\Resource');
		$mockResource
			->expects($this->any())
			->method('getResourcePointer')
			->will($this->returnValue($mockResourcePointer));

		$this->image = $this->getAccessibleMock('TYPO3\Media\Domain\Model\Image', array('initialize'), array('resource' => $mockResource));
	}

	/**
	 * @test
	 */
	public function orientationReturnedCorrectlyForLandscapeImage() {
		$this->image->_set('width', 480);
		$this->image->_set('height', 320);

		$this->assertTrue($this->image->isOrientationLandscape());
		$this->assertFalse($this->image->isOrientationPortrait());
		$this->assertFalse($this->image->isOrientationSquare());
		$this->assertEquals(\TYPO3\Media\Domain\Model\ImageInterface::ORIENTATION_LANDSCAPE, $this->image->getOrientation());
	}

	/**
	 * @test
	 */
	public function orientationReturnedCorrectlyForPortraitImage() {
		$this->image->_set('width', 320);
		$this->image->_set('height', 480);

		$this->assertFalse($this->image->isOrientationLandscape());
		$this->assertTrue($this->image->isOrientationPortrait());
		$this->assertFalse($this->image->isOrientationSquare());
		$this->assertEquals(\TYPO3\Media\Domain\Model\ImageInterface::ORIENTATION_PORTRAIT, $this->image->getOrientation());
	}

	/**
	 * @test
	 */
	public function orientationReturnedCorrectlyForSquareImage() {
		$this->image->_set('width', 480);
		$this->image->_set('height', 480);

		$this->assertFalse($this->image->isOrientationLandscape());
		$this->assertFalse($this->image->isOrientationPortrait());
		$this->assertTrue($this->image->isOrientationSquare());
		$this->assertEquals(\TYPO3\Media\Domain\Model\ImageInterface::ORIENTATION_SQUARE, $this->image->getOrientation());
	}


	/**
	 * @test
	 */
	public function aspectRatioReturnedCorrectlyForLandscapeImage() {
		$this->image->_set('width', 480);
		$this->image->_set('height', 320);

		$this->assertEquals(1.5, $this->image->getAspectRatio());
		$this->assertEquals(1.5, $this->image->getAspectRatio(FALSE));
		$this->assertEquals(1.5, $this->image->getAspectRatio(TRUE));
	}

	/**
	 * @test
	 */
	public function aspectRatioReturnedCorrectlyForPortraitImage() {
		$this->image->_set('width', 320);
		$this->image->_set('height', 480);

		$this->assertEquals(1.5, $this->image->getAspectRatio());
		$this->assertEquals(1.5, $this->image->getAspectRatio(FALSE));
		$this->assertEquals(0.6667, round($this->image->getAspectRatio(TRUE), 4));
	}

	/**
	 * @test
	 */
	public function aspectRatioReturnedCorrectlyForSquareImage() {
		$this->image->_set('width', 480);
		$this->image->_set('height', 480);

		$this->assertEquals(1, $this->image->getAspectRatio());
		$this->assertEquals(1, $this->image->getAspectRatio(FALSE));
		$this->assertEquals(1, $this->image->getAspectRatio(TRUE));
	}

	/**
	 * @test
	 */
	public function imageVariantsGetOriginalImageActuallyReturnsOriginalImage() {
		$variant = $this->image->createImageVariant(array('dummy'));
		$this->assertSame($variant->getOriginalImage(), $this->image);
	}

	/**
	 * @test
	 */
	public function widthAndHeightIsCastToIntegerWhenCreatingThumbnail() {
		$variant = $this->image->getThumbnail('4', '3');
		$processingInstructions = $variant->getProcessingInstructions();
		$this->assertInternalType('integer', $processingInstructions[0]['options']['size']['width']);
		$this->assertInternalType('integer', $processingInstructions[0]['options']['size']['height']);
	}

	/**
	 * @test
	 */
	public function creatingImageVariantWorks() {
		$this->image->createImageVariant(array('dummy'));
		$this->image->createImageVariant(array('foo'));
		$this->assertCount(2, $this->image->getImageVariants());
	}

	/**
	 * @test
	 */
	public function creatingImageVariantWithSameProcessingInstructionsReplaceEachOther() {
		$this->image->createImageVariant(array('dummy'));
		$this->image->createImageVariant(array('foo'));
		$this->image->createImageVariant(array('dummy'));
		$this->image->createImageVariant(array('foo'));
		$this->assertCount(2, $this->image->getImageVariants());
	}

	/**
	 * @test
	 */
	public function removingImageVariantsWorks() {
		$firstVariant = $this->image->createImageVariant(array('dummy'));
		$secondVariant = $this->image->createImageVariant(array('foo'));
		$thirdVariant = $this->image->createImageVariant(array('bar'));
		$this->assertCount(3, $this->image->getImageVariants());

		$this->image->removeImageVariant($thirdVariant);
		$this->image->removeImageVariant($secondVariant);
		$remainingVariants = $this->image->getImageVariants();
		$this->assertCount(1, $remainingVariants);

		$remainingVariant = reset($remainingVariants);
		$this->assertSame($remainingVariant, $firstVariant);
	}


	/**
	 * @test
	 */
	public function createImageVariantsByAliasWorks() {
		$variant = $this->image->createImageVariant(array('dummy'), 'someAliasName');
		$this->assertSame('someAliasName', $variant->getAlias());
		$this->assertCount(1, $this->image->getImageVariants());
	}

	/**
	 * @test
	 */
	public function gettingImageVariantsByAliasWorks() {
		$firstVariant = $this->image->createImageVariant(array('dummy'), 'firstAliasName');
		$secondVariant = $this->image->createImageVariant(array('foobar'), 'secondAliasName');
		$this->assertCount(2, $this->image->getImageVariants());
		$this->assertSame($firstVariant, $this->image->getImageVariantByAlias('firstAliasName'));
		$this->assertSame($secondVariant, $this->image->getImageVariantByAlias('secondAliasName'));
	}

	/**
	 * @test
	 */
	public function gettingNotExistingAliasReturnsNull() {
		$this->assertNull($this->image->getImageVariantByAlias('anAliasThatIsNotPresent'));
	}

	/**
	 * @test
	 */
	public function removingImageVariantByAliasWorks() {
		$this->image->createImageVariant(array('dummy'), 'firstAliasName');
		$secondVariant = $this->image->createImageVariant(array('foobar'), 'secondAliasName');
		$this->assertCount(2, $this->image->getImageVariants());

		$this->image->removeImageVariantByAlias('firstAliasName');
		$remainingVariants = $this->image->getImageVariants();
		$this->assertCount(1, $remainingVariants);
		$this->assertSame($secondVariant, reset($remainingVariants));
	}

}
