<?php
namespace TYPO3\Media\Tests\Unit\Validator;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
