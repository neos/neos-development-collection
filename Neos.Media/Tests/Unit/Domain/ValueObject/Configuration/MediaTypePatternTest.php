<?php
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\ValueObject\Configuration\MediaTypePattern;

class MediaTypePatternTest extends UnitTestCase
{
    /**
     * @return array
     */
    public static function validMediaTypePatterns(): array
    {
        return [
            ['/image\/.*/'],
            ['|image/jpe?g|']
        ];
    }

    /**
     * @param $mediaTypePatternAsString
     */
    #[DataProvider('validMediaTypePatterns')]
    #[Test]
    public function validMediaTypePatternsAreAccepted($mediaTypePatternAsString): void
    {
        $mediaType = new MediaTypePattern($mediaTypePatternAsString);
        self::assertSame($mediaTypePatternAsString, (string)$mediaType);
    }

    /**
     * @return array
     */
    public static function invalidMediaTypePatterns(): array
    {
        return [
            [''],
            ['something'],
            ['☀️☠️'],
            ['***']
        ];
    }

    /**
     * @param $mediaTypePatternAsString
     */
    #[DataProvider('invalidMediaTypePatterns')]
    #[Test]
    public function invalidMediaTypePatternsAreRejected($mediaTypePatternAsString): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MediaTypePattern($mediaTypePatternAsString);
    }

    #[Test]
    public function matchesChecksIfMediaTypeMatchesPattern(): void
    {
        $mediaTypePattern = new MediaTypePattern('~image/(jpe?g|png)~');

        self::assertTrue($mediaTypePattern->matches('image/jpeg'));
        self::assertTrue($mediaTypePattern->matches('image/jpg'));
        self::assertTrue($mediaTypePattern->matches('image/png'));

        self::assertFalse($mediaTypePattern->matches('image/svg'));
        self::assertFalse($mediaTypePattern->matches('application/json'));
    }
}
