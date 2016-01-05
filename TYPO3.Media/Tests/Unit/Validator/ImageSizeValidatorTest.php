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
 * Testcase for the ImageSizeValidator
 *
 */
class ImageSizeValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function validatorReturnsErrorsIfGivenValueIsNoImage()
    {
        $validator = new \TYPO3\Media\Validator\ImageSizeValidator(array('minimumWidth' => 123));

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
            array(array('someNonExistingOption' => 123)),
            array(array('minimumWidth' => 123, 'maximumWidth' => 122)),
            array(array('minimumHeight' => 123, 'maximumHeight' => 122)),
            array(array('minimumResolution' => 15000, 'maximumResolution' => 14999)),
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
        $validator = new \TYPO3\Media\Validator\ImageSizeValidator($options);
        $image = $this->getMock('TYPO3\Media\Domain\Model\ImageInterface');
        $validator->validate($image);
    }

    /**
     * @return array
     */
    public function validatorTestsDataProvider()
    {
        return array(
            array(array('minimumWidth' => 123), 122, 0, false),
            array(array('minimumWidth' => 123), 123, 0, true),

            array(array('minimumHeight' => 123), 0, 122, false),
            array(array('minimumHeight' => 123), 0, 123, true),

            array(array('maximumWidth' => 123), 124, 0, false),
            array(array('maximumWidth' => 123), 123, 0, true),

            array(array('maximumHeight' => 123), 0, 124, false),
            array(array('maximumHeight' => 123), 0, 123, true),

            array(array('minimumResolution' => 6150), 123, 49, false),
            array(array('minimumResolution' => 6150), 123, 50, true),

            array(array('maximumResolution' => 6150), 123, 51, false),
            array(array('maximumResolution' => 6150), 123, 50, true),

            array(array('minimumWidth' => 123, 'minimumHeight' => 50, 'maximumWidth' => 123, 'maximumHeight' => 50), 123, 51, false),
            array(array('minimumWidth' => 123, 'minimumHeight' => 50, 'maximumWidth' => 123, 'maximumHeight' => 50), 122, 50, false),
            array(array('minimumWidth' => 123, 'minimumHeight' => 50, 'maximumWidth' => 123, 'maximumHeight' => 50), 123, 50, true),

            array(array('minimumWidth' => 123, 'minimumHeight' => 50, 'minimumResolution' => 6050, 'maximumResolution' => 6050), 123, 50, false),
            array(array('minimumWidth' => 123, 'minimumHeight' => 50, 'minimumResolution' => 6150, 'maximumResolution' => 6150), 123, 50, true),
        );
    }

    /**
     * @test
     * @dataProvider validatorTestsDataProvider
     * @param array $options
     * @param integer $imageWidth
     * @param integer $imageHeight
     * @param boolean $isValid
     */
    public function validatorTests(array $options, $imageWidth, $imageHeight, $isValid)
    {
        $validator = new \TYPO3\Media\Validator\ImageSizeValidator($options);
        $image = $this->getMock('TYPO3\Media\Domain\Model\ImageInterface');
        $image->expects($this->any())->method('getWidth')->will($this->returnValue($imageWidth));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue($imageHeight));

        $validationResult = $validator->validate($image);
        if ($isValid) {
            $this->assertFalse($validationResult->hasErrors());
        } else {
            $this->assertTrue($validationResult->hasErrors());
        }
    }
}
