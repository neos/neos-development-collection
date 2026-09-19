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

use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NodePathTest extends TestCase
{
    #[DataProvider('serializedPathProvider')]
    public function testDeserialization(
        string $serializedPath,
        bool $expectedEmptyState,
        /** @var array<int,NodeName> $expectedParts */
        array $expectedParts,
        int $expectedLength
    ): void {
        $subject = NodePath::fromString($serializedPath);

        self::assertSame($serializedPath, $subject->serializeToString());
        self::assertSame($expectedEmptyState, $subject->isEmpty());
        self::assertEquals($expectedParts, $subject->getParts());
        self::assertSame($expectedLength, $subject->getLength());
    }

    public static function serializedPathProvider(): iterable
    {
        yield 'nonRoot' => [
            'serializedPath' => 'child/grandchild',
            'expectedEmptyState' => false,
            'expectedParts' => [
                NodeName::fromString('child'),
                NodeName::fromString('grandchild'),
            ],
            'expectedLength' => 2
        ];

        yield 'root' => [
            'serializedPath' => '',
            'expectedEmptyState' => true,
            'expectedParts' => [],
            'expectedLength' => 0
        ];
    }
}
