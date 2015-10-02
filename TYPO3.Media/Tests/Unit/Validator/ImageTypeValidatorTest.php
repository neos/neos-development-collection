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

/**
 * Testcase for the ImageTypeValidator
 *
 */
class ImageTypeValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function validatorReturnsErrorsIfGivenValueIsNoImage()
    {
        $validator = new \TYPO3\Media\Validator\ImageTypeValidator(array('allowedTypes' => array('png')));

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
    public function invalidOptionsTests(array $options)
    {
        $validator = new \TYPO3\Media\Validator\ImageTypeValidator($options);
        $image = $this->getMock('TYPO3\Media\Domain\Model\ImageInterface');
        $validator->validate($image);
    }

    /**
     * @return array
     */
    public function validatorTestsDataProvider()
    {
        return array(
            array(array('allowedTypes' => array('png')), null, false),
            array(array('allowedTypes' => array('png')), IMAGETYPE_BMP, false),
            array(array('allowedTypes' => array('png')), IMAGETYPE_PNG, true),
            array(array('allowedTypes' => array('jpeg', 'gif')), IMAGETYPE_ICO, false),
            array(array('allowedTypes' => array('jpeg', 'gif')), IMAGETYPE_GIF, true),
        );
    }

    /**
     * @test
     * @dataProvider validatorTestsDataProvider
     * @param array $options
     * @param integer $imageType (one of the IMAGETYPE_* constants)
     * @param boolean $isValid
     */
    public function validatorTests(array $options, $imageType, $isValid)
    {
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
