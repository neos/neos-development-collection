<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Projection\ContentGraph;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use PHPUnit\Framework\TestCase;

class AbsoluteNodePathTest extends TestCase
{
    /**
     * @dataProvider patternMatchProvider
     */
    public function testPatternIsMatchedByString(
        string $serializedPath,
        bool $expectedResult
    ) {
        self::assertSame($expectedResult, AbsoluteNodePath::patternIsMatchedByString($serializedPath));
    }

    /**
     * @return iterable<string,mixed>
     */
    public static function patternMatchProvider(): iterable
    {
        yield 'root' => [
            'serializedPath' => '/<Neos.ContentRepository:Root>',
            'expectedResult' => true
        ];

        yield 'nonRoot' => [
            'serializedPath' => '/<Neos.ContentRepository:Root>/child/grandchild',
            'expectedResult' => true
        ];

        yield 'invalidPattern' => [
            'serializedPath' => '/<Neos.ContentRepository:Root/child/grandchild',
            'expectedResult' => false
        ];

        yield 'anotherInvalidPattern' => [
            'serializedPath' => 'Neos.ContentRepository:Root/child/grandchild',
            'expectedResult' => false
        ];

        yield 'invalidRoot' => [
            'serializedPath' => '/',
            'expectedResult' => false
        ];

        yield 'invalidNonRoot' => [
            'serializedPath' => '/child/grandchild',
            'expectedResult' => false
        ];
    }

    /**
     * @dataProvider serializedPathProvider
     */
    public function testDeserialization(
        string $serializedPath,
        string $expectedRelativePath,
        ?NodeTypeName $expectedRootNodeTypeName,
        bool $expectedRootState,
        /** @var array<int,NodeName> $expectedParts */
        array $expectedParts,
        int $expectedDepth
    ): void {
        $subject = AbsoluteNodePath::fromString($serializedPath);

        self::assertSame($expectedRelativePath, $subject->path->value);
        self::assertSame($expectedRootNodeTypeName, $subject->rootNodeTypeName);
        self::assertSame($expectedRootState, $subject->isRoot());
        self::assertEquals($expectedParts, $subject->getParts());
        self::assertSame($expectedDepth, $subject->getDepth());
        self::assertSame($serializedPath, $subject->serializeToString());
    }

    /**
     * @return iterable<string,mixed>
     */
    public static function serializedPathProvider(): iterable
    {
        yield 'root' => [
            'serializedPath' => '/<Neos.ContentRepository:Root>',
            'expectedRelativePath' => '/',
            'expectedRootNodeTypeName' => NodeTypeName::fromString('Neos.ContentRepository:Root'),
            'expectedRootState' => true,
            'expectedParts' => [],
            'expectedDepth' => 0
        ];

        yield 'nonRoot' => [
            'serializedPath' => '/<Neos.ContentRepository:Root>/child/grandchild',
            'expectedRelativePath' => 'child/grandchild',
            'expectedRootNodeTypeName' => NodeTypeName::fromString('Neos.ContentRepository:Root'),
            'expectedRootState' => false,
            'expectedParts' => [
                NodeName::fromString('child'),
                NodeName::fromString('grandchild'),
            ],
            'expectedDepth' => 2
        ];
    }
}
