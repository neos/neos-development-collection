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

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Media\Validator\ImageTypeValidator;

/**
 * Test case for the ImageTypeValidator
 */
class ImageTypeValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validatorReturnsErrorsIfGivenValueIsNoImage()
    {
        $validator = new ImageTypeValidator(array('allowedTypes' => array('png')));
        $value = new \stdClass();
        $this->assertTrue($validator->validate($value)->hasErrors());
    }

    /**
     * @return array
     */
    public function validatorTestsDataProvider()
    {
        return array(
            array(array('allowedTypes' => array('png')), null, false),
            array(array('allowedTypes' => array('png')), 'image/bmp', false),
            array(array('allowedTypes' => array('png')), 'image/png', true),
            array(array('allowedTypes' => array('jpeg', 'gif')), 'image/ico', false),
            array(array('allowedTypes' => array('jpeg', 'gif')), 'image/gif', true),
        );
    }

    /**
     * @test
     * @dataProvider validatorTestsDataProvider
     * @param array $options
     * @param string $actualMediaType
     * @param boolean $supposedToBeValid
     */
    public function validatorTests(array $options, $actualMediaType, $supposedToBeValid)
    {
        $image = $this->getMockBuilder('TYPO3\Media\Domain\Model\Image')->disableOriginalConstructor()->getMock();
        $image->expects($this->any())->method('getMediaType')->will($this->returnValue($actualMediaType));

        $validator = new ImageTypeValidator($options);
        $validationResult = $validator->validate($image);
        $this->assertEquals($supposedToBeValid, !$validationResult->hasErrors());
    }
}
