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
        $image = $this->createMock('TYPO3\Media\Domain\Model\ImageInterface');
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
        $image = $this->createMock('TYPO3\Media\Domain\Model\ImageInterface');
        $image->expects($this->any())->method('getOrientation')->will($this->returnValue($imageOrientation));

        $validationResult = $validator->validate($image);
        if ($isValid) {
            $this->assertFalse($validationResult->hasErrors());
        } else {
            $this->assertTrue($validationResult->hasErrors());
        }
    }
}
