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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\ValueObject\Configuration\MediaTypePattern;

class MediaTypePatternTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function validMediaTypePatterns(): array
    {
        return [
            ['/image\/.*/'],
            ['|image/jpe?g|']
        ];
    }

    /**
     * @param $mediaTypePatternAsString
     * @dataProvider validMediaTypePatterns()
     * @test
     */
    public function validMediaTypePatternsAreAccepted($mediaTypePatternAsString): void
    {
        $mediaType = new MediaTypePattern($mediaTypePatternAsString);
        self::assertSame($mediaTypePatternAsString, (string)$mediaType);
    }

    /**
     * @return array
     */
    public function invalidMediaTypePatterns(): array
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
     * @test
     * @dataProvider invalidMediaTypePatterns()
     */
    public function invalidMediaTypePatternsAreRejected($mediaTypePatternAsString): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MediaTypePattern($mediaTypePatternAsString);
    }

    /**
     * @test
     */
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
