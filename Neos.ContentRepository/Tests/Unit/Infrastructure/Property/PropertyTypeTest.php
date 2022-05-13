<?php
namespace Neos\ContentRepository\Tests\Unit\Infrastructure\Property;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Tests\Unit\Fixtures\PostalAddress;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Model\Asset;
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
    public function testIsMatchedBy(array $declarationsByType, array $validValues, array $invalidValues): void
    {
        foreach ($declarationsByType as $declaration) {
            $subject = PropertyType::fromNodeTypeDeclaration(
                $declaration,
                PropertyName::fromString('test'),
                NodeTypeName::fromString('Neos.ContentRepository:Test')
            );
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
        $asset = new Asset(new PersistentResource());
        $date = \DateTimeImmutable::createFromFormat(\DateTimeInterface::W3C, '2020-08-20T18:56:15+00:00');
        $uri = new Uri('https://www.neos.io');
        $postalAddress = PostalAddress::dummy();

        return [
            [
                ['bool', '?bool', 'boolean', '?boolean'],
                [$bool, null],
                [0, $int, 0.0, $float, '', $string, [], $array, $date, $uri, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                ['int', '?int', 'integer', '?integer'],
                [42, null],
                [$bool, $float, $string, $array, $date, $uri, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                ['float', '?float', 'double', '?double'],
                [4.2, null],
                [$bool, $int, $string, $array, $date, $uri, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                ['string', '?string'],
                ['', null],
                [$bool, $int, $float, $array, $date, $uri, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                ['array', '?array'],
                [[], $array, [$asset], null],
                [$bool, $int, $float, $string, $date, $uri, $postalAddress, $image, $asset]
            ],
            [
                [\DateTime::class, '?' . \DateTime::class, \DateTimeImmutable::class, '?' . \DateTimeImmutable::class, \DateTimeInterface::class, '?' . \DateTimeInterface::class],
                [$date, null],
                [$bool, $int, $float, $string, $array, $uri, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                ['Uri', '?Uri', Uri::class, '?' . Uri::class, UriInterface::class, '?' . UriInterface::class],
                [$uri, null],
                [$bool, $int, $float, $string, $array, $date, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                [PostalAddress::class, '?' . PostalAddress::class],
                [$postalAddress, null],
                [$bool, $int, $float, $string, $array, $date, $uri, $image, $asset, [$asset]]
            ],
            [
                [ImageInterface::class, '?' . ImageInterface::class],
                [$image, null],
                [$bool, $int, $float, $string, $array, $date, $uri, $postalAddress, $asset, [$image]]
            ],
            [
                [Asset::class, '?' . Asset::class],
                [$asset, $image, null],
                [$bool, $int, $float, $string, $array, $date, $uri, $postalAddress, [$asset]]
            ],
            [
                ['array<' . Asset::class . '>'],
                [[$asset], [$image], null],
                [$bool, $int, $float, $string, $array, $date, $uri, $postalAddress, $image, $asset]
            ]
        ];
    }

    /**
     * @dataProvider declarationTypeProvider
     * @param array $declaredTypes
     * @param string $expectedSerializationType
     */
    public function testGetSerializationType(array $declaredTypes, string $expectedSerializationType): void
    {
        foreach ($declaredTypes as $declaredType) {
            $actualSerializationType = PropertyType::fromNodeTypeDeclaration(
                $declaredType,
                PropertyName::fromString('test'),
                NodeTypeName::fromString('Neos.ContentRepository:Test')
            )->getSerializationType();
            Assert::assertSame(
                $expectedSerializationType,
                $actualSerializationType,
                'Serialization type does not match for declared type "' . $declaredType . '". Expected "' . $expectedSerializationType . '", got "' . $actualSerializationType . '"'
            );
        }
    }

    public function declarationTypeProvider(): array
    {
        return [
            [['bool', '?bool', 'boolean', '?boolean'], 'boolean'],
            [['int', '?int', 'integer', '?integer'], 'integer'],
            [['float', '?float', 'double', '?double'], 'float'],
            [['string', '?string'], 'string'],
            [['array', '?array'], 'array'],
            [['DateTime', '?DateTime', 'DateTimeImmutable', '?DateTimeImmutable', 'DateTimeInterface', '?DateTimeInterface'], 'DateTimeImmutable'],
            [['Uri', '?Uri', Uri::class, '?' . Uri::class, UriInterface::class, '?' . UriInterface::class], Uri::class],
            [[PostalAddress::class, '?' . PostalAddress::class], PostalAddress::class],
            [[ImageInterface::class, '?' . ImageInterface::class], ImageInterface::class],
            [[Asset::class, '?' . Asset::class], Asset::class],
            [['array<' . Asset::class . '>', '?array<' . Asset::class . '>'], 'array<' . Asset::class . '>'],
        ];
    }
}
