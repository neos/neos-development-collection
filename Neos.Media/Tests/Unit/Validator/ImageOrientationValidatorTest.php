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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Exception\InvalidValidationOptionsException;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Validator\ImageOrientationValidator;

/**
 * Testcase for the ImageOrientationValidator
 *
 */
class ImageOrientationValidatorTest extends UnitTestCase
{
    #[Test]
    public function validatorReturnsErrorsIfGivenValueIsNoImage()
    {
        $validator = new ImageOrientationValidator(['allowedOrientations' => [ImageInterface::ORIENTATION_LANDSCAPE]]);

        $value = new \stdClass();
        self::assertTrue($validator->validate($value)->hasErrors());
    }

    /**
     * @return array
     */
    public function invalidOptionsTestsDataProvider()
    {
        return [
            [[]],
            [['allowedOrientations' => ImageInterface::ORIENTATION_LANDSCAPE]],
            [['allowedOrientations' => []]],
            [['allowedOrientations' => ['nonExistingOrientation']]],
            [['allowedOrientations' => ['square', 'portrait', 'landscape']]],
        ];
    }

    /**
     * @param array $options
     */
    #[DataProvider('invalidOptionsTestsDataProvider')]
    #[Test]
    public function invalidOptionsTests(array $options)
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $validator = new ImageOrientationValidator($options);
        $image = $this->createMock(ImageInterface::class);
        $validator->validate($image);
    }

    /**
     * @return array
     */
    public function validatorTestsDataProvider()
    {
        return [
            [['allowedOrientations' => ['landscape']], null, false],
            [['allowedOrientations' => ['landscape']], 'landscape', true],
            [['allowedOrientations' => [ImageInterface::ORIENTATION_LANDSCAPE]], 'landscape', true],
            [['allowedOrientations' => ['square', 'landscape']], 'portrait', false],
            [['allowedOrientations' => ['square', 'portrait']], 'portrait', true],
        ];
    }

    /**
     * @param array $options
     * @param integer $imageOrientation (one of the ImageOrientation_* constants)
     * @param boolean $isValid
     */
    #[DataProvider('validatorTestsDataProvider')]
    #[Test]
    public function validatorTests(array $options, $imageOrientation, $isValid)
    {
        $validator = new ImageOrientationValidator($options);
        $image = $this->createMock(ImageInterface::class);
        $image->expects(self::any())->method('getOrientation')->will(self::returnValue($imageOrientation));

        $validationResult = $validator->validate($image);
        if ($isValid) {
            self::assertFalse($validationResult->hasErrors());
        } else {
            self::assertTrue($validationResult->hasErrors());
        }
    }
}
