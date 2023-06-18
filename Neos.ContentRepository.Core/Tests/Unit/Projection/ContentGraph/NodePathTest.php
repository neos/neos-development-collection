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
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use PHPUnit\Framework\TestCase;

class NodePathTest extends TestCase
{
    /**
     * @dataProvider serializedPathProvider
     */
    public function testDeserialization(
        string $serializedPath,
        string $expectedRelativePath,
        ?NodeTypeName $expectedRootNodeTypeName,
        bool $expectedRootState,
        bool $expectedAbsoluteState,
        /** @var array<int,NodeName> $expectedParts */
        array $expectedParts,
        ?int $expectedDepth
    ): void {
        $subject = NodePath::fromString($serializedPath);

        self::assertSame($expectedRelativePath, $subject->path);
        self::assertSame($expectedRootNodeTypeName, $subject->rootNodeTypeName);
        self::assertSame($expectedRootState, $subject->isRoot());
        self::assertSame($expectedAbsoluteState, $subject->isAbsolute());
        self::assertEquals($expectedParts, $subject->getParts());
        if (!is_null($expectedDepth)) {
            self::assertSame($expectedDepth, $subject->getDepth());
        }
        self::assertSame($serializedPath, $subject->__toString());
    }

    public static function serializedPathProvider(): iterable
    {
        yield 'relative' => [
            'serializedPath' => 'child/grandchild',
            'expectedRelativePath' => 'child/grandchild',
            'expectedRootNodeTypeName' => null,
            'expectedRootState' => false,
            'expectedAbsoluteState' => false,
            'expectedParts' => [
                NodeName::fromString('child'),
                NodeName::fromString('grandchild'),
            ],
            'expectedDepth' => null
        ];

        yield 'emptyAbsolute' => [
            'serializedPath' => '/<Neos.ContentRepository:Root>',
            'expectedRelativePath' => '/',
            'expectedRootNodeTypeName' => NodeTypeName::fromString('Neos.ContentRepository:Root'),
            'expectedRootState' => true,
            'expectedAbsoluteState' => true,
            'expectedParts' => [],
            'expectedDepth' => 0
        ];

        yield 'absolute' => [
            'serializedPath' => '/<Neos.ContentRepository:Root>/child/grandchild',
            'expectedRelativePath' => 'child/grandchild',
            'expectedRootNodeTypeName' => NodeTypeName::fromString('Neos.ContentRepository:Root'),
            'expectedRootState' => false,
            'expectedAbsoluteState' => true,
            'expectedParts' => [
                NodeName::fromString('child'),
                NodeName::fromString('grandchild'),
            ],
            'expectedDepth' => 2
        ];
    }
}
