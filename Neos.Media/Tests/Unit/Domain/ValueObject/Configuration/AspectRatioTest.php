<?php
declare(strict_types=1);

namespace Neos\Media\Tests\Unit\Domain\ValueObject\Configuration;

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
use Neos\Media\Domain\ValueObject\Configuration\AspectRatio;

class AspectRatioTest extends UnitTestCase
{
    /**
     * @return void
     */
    #[Test]
    public function aspectRatioCanBeConvertedToString(): void
    {
        $aspectRatio = new AspectRatio(16, 9);
        self::assertSame('16:9', (string)$aspectRatio);
    }

    /**
     * @return void
     */
    #[Test]
    public function aspectRatioCanBeCreatedFromString(): void
    {
        $aspectRatio = AspectRatio::fromString('16:9');

        self::assertSame(16, $aspectRatio->getWidth());
        self::assertSame(9, $aspectRatio->getHeight());
    }

    /**
     * @return array
     */
    public function validStrings(): array
    {
        return [
            ['16:9'],
            ['1:1'],
            ['24:98'],
            ['500:600']
        ];
    }

    /**
     * @param string $validString
     * @return void
     */
    #[DataProvider('validStrings')]
    #[Test]
    public function validStringIsAccepted(string $validString): void
    {
        $aspectRatio = AspectRatio::fromString($validString);
        self::assertSame($validString, (string)$aspectRatio);
    }

    /**
     * @return array
     */
    public function invalidStrings(): array
    {
        return [
            ['invalid'],
            ['16 9'],
            ['something:else'],
            ['something:8'],
            ['1:-8'],
            ['1:foo'],
        ];
    }

    /**
     * @param string $invalidString
     * @return void
     */
    #[DataProvider('invalidStrings')]
    #[Test]
    public function invalidStringIsRejected(string $invalidString): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1552641724);
        AspectRatio::fromString($invalidString);
    }

    /**
     * @return array
     */
    public function aspectRatiosAndOrientations(): array
    {
        return [
            ['4:3', AspectRatio::ORIENTATION_LANDSCAPE],
            ['3:4', AspectRatio::ORIENTATION_PORTRAIT],
            ['16:9', AspectRatio::ORIENTATION_LANDSCAPE],
            ['9:16', AspectRatio::ORIENTATION_PORTRAIT],
            ['1:1', AspectRatio::ORIENTATION_SQUARE],
            ['8:8', AspectRatio::ORIENTATION_SQUARE]
        ];
    }

    /**
     * @param string $aspectRatioAsString
     * @param string $expectedOrientation
     */
    #[DataProvider('aspectRatiosAndOrientations')]
    #[Test]
    public function getOrientationReturnsCorrectValue(string $aspectRatioAsString, string $expectedOrientation): void
    {
        $aspectRatio = AspectRatio::fromString($aspectRatioAsString);
        self::assertSame($expectedOrientation, $aspectRatio->getOrientation());

        switch ($expectedOrientation) {
            case AspectRatio::ORIENTATION_LANDSCAPE:
                self::assertTrue($aspectRatio->isOrientationLandscape());
                break;
            case AspectRatio::ORIENTATION_PORTRAIT:
                self::assertTrue($aspectRatio->isOrientationPortrait());
                break;
            case AspectRatio::ORIENTATION_SQUARE:
                self::assertTrue($aspectRatio->isOrientationSquare());
                break;
        }
    }
}
