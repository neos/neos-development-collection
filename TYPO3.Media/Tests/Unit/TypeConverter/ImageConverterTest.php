<?php
namespace TYPO3\Media\Tests\Unit\TypeConverter;

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
 * Testcase for the ImageConverter
 */
class ImageConverterTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Media\TypeConverter\ImageConverter
	 */
	protected $converter;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->converter = new \TYPO3\Media\TypeConverter\ImageConverter();
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('array'), $this->converter->getSupportedSourceTypes());
		$this->assertEquals('TYPO3\Media\Domain\Model\Image', $this->converter->getSupportedTargetType());
		$this->assertEquals(1, $this->converter->getPriority());
	}

	/**
	 * @return array
	 */
	public function canConvertFromDataProvider() {
		return array(
			array(array(), 'TYPO3\Media\Domain\Model\Image', TRUE),
			array(array('__identity' => 'foo'), 'TYPO3\Media\Domain\Model\Image', FALSE),
			array(array(), 'TYPO3\Media\Domain\Model\ImageInterface', TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider canConvertFromDataProvider
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param boolean $expected
	 */
	public function canConvertFromTests($source, $targetType, $expected) {
		$this->assertEquals($expected, $this->converter->canConvertFrom($source, $targetType));
	}

	/**
	 * @test
	 */
	public function convertFromReturnsNullIfResourcePropertyIsNotConverted() {
		$this->assertNull($this->converter->convertFrom(array(), 'TYPO3\Media\Domain\Model\Image'));
		$this->assertNull($this->converter->convertFrom(array(), 'TYPO3\Media\Domain\Model\Image', array('resource' => 'bar')));
	}

}
