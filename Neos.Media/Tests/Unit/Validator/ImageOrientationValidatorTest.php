<?php
namespace Neos\Media\Tests\Unit\Validator;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Validator\ImageOrientationValidator;

/**
 * Testcase for the ImageOrientationValidator
 *
 */
class ImageOrientationValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validatorReturnsErrorsIfGivenValueIsNoImage()
    {
        $validator = new ImageOrientationValidator(array('allowedOrientations' => array(ImageInterface::ORIENTATION_LANDSCAPE)));

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
     * @expectedException \Neos\Flow\Validation\Exception\InvalidValidationOptionsException
     * @param array $options
     */
    public function invalidOptionsTests(array $options)
    {
        $validator = new ImageOrientationValidator($options);
        $image = $this->createMock(ImageInterface::class);
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
        $validator = new ImageOrientationValidator($options);
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->any())->method('getOrientation')->will($this->returnValue($imageOrientation));

        $validationResult = $validator->validate($image);
        if ($isValid) {
            $this->assertFalse($validationResult->hasErrors());
        } else {
            $this->assertTrue($validationResult->hasErrors());
        }
    }
}
