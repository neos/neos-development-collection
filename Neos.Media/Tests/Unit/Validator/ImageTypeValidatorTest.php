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
use Neos\Media\Domain\Model\Image;
use Neos\Media\Validator\ImageTypeValidator;

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
        $validator = new ImageTypeValidator(['allowedTypes' => ['png']]);
        $value = new \stdClass();
        self::assertTrue($validator->validate($value)->hasErrors());
    }

    /**
     * @return array
     */
    public function validatorTestsDataProvider()
    {
        return [
            [['allowedTypes' => ['png']], null, false],
            [['allowedTypes' => ['png']], 'image/bmp', false],
            [['allowedTypes' => ['png']], 'image/png', true],
            [['allowedTypes' => ['jpeg', 'gif']], 'image/ico', false],
            [['allowedTypes' => ['jpeg', 'gif']], 'image/gif', true],
        ];
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
        $image = $this->getMockBuilder(Image::class)->disableOriginalConstructor()->getMock();
        $image->expects(self::any())->method('getMediaType')->will(self::returnValue($actualMediaType));

        $validator = new ImageTypeValidator($options);
        $validationResult = $validator->validate($image);
        self::assertEquals($supposedToBeValid, !$validationResult->hasErrors());
    }
}
