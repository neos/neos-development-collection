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

use TYPO3\Media\Domain\Model\ImageInterface;

/**
 * Testcase for the ImageOrientationValidator
 *
 */
class ImageOrientationValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function validatorReturnsErrorsIfGivenValueIsNoImage()
    {
        $validator = new \TYPO3\Media\Validator\ImageOrientationValidator(array('allowedOrientations' => array(ImageInterface::ORIENTATION_LANDSCAPE)));

        $value = new \stdClass();
        $this->assertTrue($validator->validate($value)->hasErrors());
    }

    /**
     * @return array
     */
    public function invalidOptionsTestsDataProvider()
    {
        return array(
            array(array()),
            array(array('allowedOrientations' => ImageInterface::ORIENTATION_LANDSCAPE)),
            array(array('allowedOrientations' => array())),
            array(array('allowedOrientations' => array('nonExistingOrientation'))),
            array(array('allowedOrientations' => array('square', 'portrait', 'landscape'))),
        );
    }

    /**
     * @test
     * @dataProvider invalidOptionsTestsDataProvider
     * @expectedException \TYPO3\Flow\Validation\Exception\InvalidValidationOptionsException
     * @param array $options
     */
    public function invalidOptionsTests(array $options)
    {
        $validator = new \TYPO3\Media\Validator\ImageOrientationValidator($options);
        $image = $this->getMock('TYPO3\Media\Domain\Model\ImageInterface');
        $validator->validate($image);
    }

    /**
     * @return array
     */
    public function validatorTestsDataProvider()
    {
        return array(
            array(array('allowedOrientations' => array('landscape')), null, false),
            array(array('allowedOrientations' => array('landscape')), 'landscape', true),
            array(array('allowedOrientations' => array(ImageInterface::ORIENTATION_LANDSCAPE)), 'landscape', true),
            array(array('allowedOrientations' => array('square', 'landscape')), 'portrait', false),
            array(array('allowedOrientations' => array('square', 'portrait')), 'portrait', true),
        );
    }

    /**
     * @test
     * @dataProvider validatorTestsDataProvider
     * @param array $options
     * @param integer $imageOrientation (one of the ImageOrientation_* constants)
     * @param boolean $isValid
     */
    public function validatorTests(array $options, $imageOrientation, $isValid)
    {
        $validator = new \TYPO3\Media\Validator\ImageOrientationValidator($options);
        $image = $this->getMock('TYPO3\Media\Domain\Model\ImageInterface');
        $image->expects($this->any())->method('getOrientation')->will($this->returnValue($imageOrientation));

        $validationResult = $validator->validate($image);
        if ($isValid) {
            $this->assertFalse($validationResult->hasErrors());
        } else {
            $this->assertTrue($validationResult->hasErrors());
        }
    }
}
