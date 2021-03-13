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
use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModels;
use Neos\ContentRepository\Intermediary\Domain\Property\PropertyType;
use Neos\ContentRepository\Intermediary\Tests\Unit\Fixtures\PostalAddress;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Image;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the PropertyType value object
 */
class PropertyTypeTest extends UnitTestCase
{
    /**
     * @dataProvider declarationAndValueProvider
     */
    public function testMatches(string $declaration, array $validValues, array $invalidValues): void
    {
        $subject = PropertyType::fromNodeTypeDeclaration($declaration);
        foreach ($validValues as $validValue) {
            Assert::assertTrue($subject->matches($validValue));
        }
        foreach ($invalidValues as $invalidValue) {
            Assert::assertFalse($subject->matches($invalidValue));
        }
    }

    public function declarationAndValueProvider(): array
    {
        $image = new Image(new PersistentResource());

        return [
            [
                'bool',
                [true, null],
                [0, 0.0, '', [], PostalAddress::dummy(), new Uri(''), $image, [$image]]
            ]
        ];
    }
}
