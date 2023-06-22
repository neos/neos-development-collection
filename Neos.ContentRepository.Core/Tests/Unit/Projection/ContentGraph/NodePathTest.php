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
use PHPUnit\Framework\TestCase;

class NodePathTest extends TestCase
{
    /**
     * @dataProvider serializedPathProvider
     */
    public function testDeserialization(
        string $serializedPath,
        string $expectedRelativePath,
        bool $expectedRootState,
        /** @var array<int,NodeName> $expectedParts */
        array $expectedParts,
        int $expectedLength
    ): void {
        $subject = NodePath::fromString($serializedPath);

        self::assertSame($expectedRelativePath, $subject->value);
        self::assertSame($expectedRootState, $subject->isRoot());
        self::assertEquals($expectedParts, $subject->getParts());
        self::assertSame($serializedPath, $subject->serializeToString());
        self::assertSame($expectedLength, $subject->getLength());
    }

    public static function serializedPathProvider(): iterable
    {
        yield 'nonRoot' => [
            'serializedPath' => 'child/grandchild',
            'expectedRelativePath' => 'child/grandchild',
            'expectedRootState' => false,
            'expectedParts' => [
                NodeName::fromString('child'),
                NodeName::fromString('grandchild'),
            ],
            'expectedLength' => 2
        ];

        yield 'root' => [
            'serializedPath' => '/',
            'expectedRelativePath' => '/',
            'expectedRootState' => true,
            'expectedParts' => [],
            'expectedLength' => 0
        ];
    }
}
