<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Infrastructure\Property;

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
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\Tests\Unit\Fixtures\PostalAddress;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

require_once(__DIR__ . '/../../Fixtures/PostalAddress.php');

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
                ['bool', 'boolean'],
                [$bool, null],
                [0, $int, 0.0, $float, '', $string, [], $array, $date, $uri, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                ['int', 'integer'],
                [42, null],
                [$bool, $float, $string, $array, $date, $uri, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                ['float', 'double'],
                [4.2, null],
                [$bool, $int, $string, $array, $date, $uri, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                ['string'],
                ['', null],
                [$bool, $int, $float, $array, $date, $uri, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                ['array'],
                [[], $array, [$asset], null],
                [$bool, $int, $float, $string, $date, $uri, $postalAddress, $image, $asset]
            ],
            [
                [\DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class],
                [$date, null],
                [$bool, $int, $float, $string, $array, $uri, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                ['Uri', Uri::class, UriInterface::class],
                [$uri, null],
                [$bool, $int, $float, $string, $array, $date, $postalAddress, $image, $asset, [$asset]]
            ],
            [
                [PostalAddress::class],
                [$postalAddress, null],
                [$bool, $int, $float, $string, $array, $date, $uri, $image, $asset, [$asset]]
            ],
            [
                [ImageInterface::class],
                [$image, null],
                [$bool, $int, $float, $string, $array, $date, $uri, $postalAddress, $asset, [$image]]
            ],
            [
                [Asset::class],
                [$asset, $image, null],
                [$bool, $int, $float, $string, $array, $date, $uri, $postalAddress, [$asset]]
            ],
            [
                ['array<' . Asset::class . '>'],
                [[$asset], [$image], null],
                [$bool, $int, $float, $string, $array, $date, $uri, $postalAddress, $image, $asset]
            ],
            [
                ['array<string>'],
                [[], [$string], [$string, ''], null],
                [$bool, $int, $float, $string, [$string, $int], $date, $uri, $postalAddress, $image, $asset, [$bool], [$float]]
            ],
            [
                ['array<integer>'],
                [[], [$int], [$int, 23432], null],
                [$bool, $int, $float, $string, $date, $uri, $postalAddress, $image, $asset, [$bool], [$float]]
            ],
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
            [['bool', 'boolean'], 'boolean'],
            [['int', 'integer'], 'integer'],
            [['float', 'double'], 'float'],
            [['string', ], 'string'],
            [['array', ], 'array'],
            [['DateTime', 'DateTimeImmutable', 'DateTimeInterface'], 'DateTimeImmutable'],
            [['Uri', Uri::class, UriInterface::class], Uri::class],
            [[PostalAddress::class], PostalAddress::class],
            [[ImageInterface::class], ImageInterface::class],
            [[Asset::class], Asset::class],
            [['array<' . Asset::class . '>'], 'array<' . Asset::class . '>'],
        ];
    }
}
