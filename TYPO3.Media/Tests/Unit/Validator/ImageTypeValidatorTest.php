<?php
namespace TYPO3\Media\Tests\Unit\Validator;

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
 * Testcase for the ImageTypeValidator
 *
 */
class ImageTypeValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function validatorReturnsErrorsIfGivenValueIsNoImage() {
		$validator = new \TYPO3\Media\Validator\ImageTypeValidator(array('allowedTypes' => array('png')));

		$value = new \stdClass();
		$this->assertTrue($validator->validate($value)->hasErrors());
	}

	/**
	 * @return array
	 */
	public function invalidOptionsTestsDataProvider() {
		return array(
			array(array()),
			array(array('allowedTypes' => 'png')),
			array(array('allowedTypes' => array())),
			array(array('allowedTypes' => array('png', 'nonExistingType'))),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidOptionsTestsDataProvider
	 * @expectedException \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
	 * @param array $options
	 */
	public function invalidOptionsTests(array $options) {
		$validator = new \TYPO3\Media\Validator\ImageTypeValidator($options);
		$image = $this->getMock('TYPO3\Media\Domain\Model\ImageInterface');
		$validator->validate($image);
	}

	/**
	 * @return array
	 */
	public function validatorTestsDataProvider() {
		return array(
			array(array('allowedTypes' => array('png')), NULL, FALSE),
			array(array('allowedTypes' => array('png')), IMAGETYPE_BMP, FALSE),
			array(array('allowedTypes' => array('png')), IMAGETYPE_PNG, TRUE),
			array(array('allowedTypes' => array('jpeg', 'gif')), IMAGETYPE_ICO, FALSE),
			array(array('allowedTypes' => array('jpeg', 'gif')), IMAGETYPE_GIF, TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider validatorTestsDataProvider
	 * @param array $options
	 * @param integer $imageType (one of the IMAGETYPE_* constants)
	 * @param boolean $isValid
	 */
	public function validatorTests(array $options, $imageType, $isValid) {
		$validator = new \TYPO3\Media\Validator\ImageTypeValidator($options);
		$image = $this->getMock('TYPO3\Media\Domain\Model\ImageInterface');
		$image->expects($this->any())->method('getType')->will($this->returnValue($imageType));

		$validationResult = $validator->validate($image);
		if ($isValid) {
			$this->assertFalse($validationResult->hasErrors());
		} else {
			$this->assertTrue($validationResult->hasErrors());
		}
	}
}
