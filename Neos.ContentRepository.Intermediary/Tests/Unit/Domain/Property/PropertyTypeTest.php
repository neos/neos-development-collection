<?php
namespace Neos\ContentRepository\Intermediary\Tests\Unit\Domain\Property;

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Intermediary\Domain\Property\PropertyType;
use Neos\ContentRepository\Intermediary\Tests\Unit\Fixtures\PostalAddress;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

/**
 * Test cases for the PropertyType value object
 */
class PropertyTypeTest extends TestCase
{
    /**
     * @dataProvider declarationAndValueProvider
     */
    public function testMatches(array $declarationsByType, array $validValues, array $invalidValues): void
    {
        foreach ($declarationsByType as $declaration) {
            $subject = PropertyType::fromNodeTypeDeclaration($declaration);
            foreach ($validValues as $validValue) {
                Assert::assertTrue($subject->isMatchedBy($validValue));
            }
            foreach ($invalidValues as $invalidValue) {
                Assert::assertFalse($subject->isMatchedBy($invalidValue));
            }
        }
    }

    public function declarationAndValueProvider(): array
    {
        $bool = true;
        $int = 42;
        $float = 4.2;
        $string = 'It\'s a graph!';
        $array = [$string];
        $image = new Image(new PersistentResource());
        $date = \DateTimeImmutable::createFromFormat(\DateTimeInterface::W3C, '2020-08-20T18:56:15+00:00');
        $uri = new Uri('https://www.neos.io');
        $postalAddress = PostalAddress::dummy();

        return [
            [
                ['bool', '?bool', 'boolean', '?boolean'],
                [$bool, null],
                [0, $int, 0.0, $float, '', $string, [], $array, $date, $uri, $postalAddress, $image, [$image]]
            ],
            [
                ['int', '?int', 'integer', '?integer'],
                [42, null],
                [$bool, $float, $string, $array, $date, $uri, $postalAddress, $image, [$image]]
            ],
            [
                ['float', '?float', 'double', '?double'],
                [4.2, null],
                [$bool, $int, $string, $array, $date, $uri, $postalAddress, $image, [$image]]
            ],
            [
                ['string', '?string'],
                ['', null],
                [$bool, $int, $float, $array, $date, $uri, $postalAddress, $image, [$image]]
            ],
            [
                ['array', '?array'],
                [[], $array, null],
                [$bool, $int, $float, $string, $date, $uri, $postalAddress, $image]
            ],
            [
                [\DateTime::class, '?' . \DateTime::class, \DateTimeImmutable::class, '?' . \DateTimeImmutable::class, \DateTimeInterface::class, '?' . \DateTimeInterface::class],
                [$date, null],
                [$bool, $int, $float, $string, $array, $uri, $postalAddress, $image, [$image]]
            ],
            [
                ['Uri', '?Uri', Uri::class, '?' . Uri::class, UriInterface::class, '?' . UriInterface::class],
                [$uri, null],
                [$bool, $int, $float, $string, $array, $date, $postalAddress, $image, [$image]]
            ],
            [
                [PostalAddress::class, '?' . PostalAddress::class],
                [$postalAddress, null],
                [$bool, $int, $float, $string, $array, $date, $uri, $image, [$image]]
            ],
            [
                [ImageInterface::class, '?' . ImageInterface::class],
                [$image, null],
                [$bool, $int, $float, $string, $array, $date, $uri, $postalAddress, [$image]]
            ],
            [
                ['array<' . ImageInterface::class . '>'],
                [[$image], null],
                [$bool, $int, $float, $string, $array, $date, $uri, $postalAddress, $image]
            ],
            [
                [ImageInterface::class . '[]'],
                [[$image], null],
                [$bool, $int, $float, $string, $array, $date, $uri, $postalAddress, $image]
            ]
        ];
    }
}
