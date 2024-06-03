<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Feature\NodeCreation\Dto;

use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for {@see NodeAggregateIdsByNodePaths}
 */
class NodeAggregateIdsByNodePathsTest extends TestCase
{
    /**
     * @param array<string,NodeAggregateId|null> $expectedNodeAggregateIdsByPath, null if the actual value is not important
     * @dataProvider subjectProvider
     */
    public function testCompleteForNodeOfType(NodeAggregateIdsByNodePaths $subject, array $expectedNodeAggregateIdsByPath): void
    {
        $nodeTypeManager = new NodeTypeManager(
            fn (): array => [
                'Neos.ContentRepository.Testing:Content' => [],
                'Neos.ContentRepository.Testing:LeafDocument' => [
                    'childNodes' => [
                        'grandchild1' => [
                            'type' => 'Neos.ContentRepository.Testing:Content'
                        ],
                        'grandchild2' => [
                            'type' => 'Neos.ContentRepository.Testing:Content'
                        ]
                    ]
                ],
                'Neos.ContentRepository.Testing:Document' => [
                    'childNodes' => [
                        'child1' => [
                            'type' => 'Neos.ContentRepository.Testing:LeafDocument'
                        ],
                        'child2' => [
                            'type' => 'Neos.ContentRepository.Testing:LeafDocument'
                        ]
                    ]
                ]
            ]
        );

        $completeSubject = $subject->completeForNodeOfType(
            NodeTypeName::fromString('Neos.ContentRepository.Testing:Document'),
            $nodeTypeManager
        );

        foreach ($expectedNodeAggregateIdsByPath as $path => $explicitExpectedNodeAggregateId) {
            $actualNodeAggregateId = $completeSubject->getNodeAggregateId(NodePath::fromString($path));
            self::assertInstanceOf(NodeAggregateId::class, $actualNodeAggregateId);
            if ($explicitExpectedNodeAggregateId instanceof NodeAggregateId) {
                self::assertTrue($actualNodeAggregateId->equals($explicitExpectedNodeAggregateId));
            }
        }
    }

    public static function subjectProvider(): iterable
    {
        yield 'emptySubject' => [
            'subject' => NodeAggregateIdsByNodePaths::createEmpty(),
            'expectedNodeAggregateIdsByPath' => [
                'child1' => null,
                'child2' => null,
                'child1/grandchild1' => null,
                'child1/grandchild2' => null,
                'child2/grandchild1' => null,
                'child2/grandchild2' => null,
            ]
        ];

        yield 'alreadyCompleteSubject' => [
            'subject' => NodeAggregateIdsByNodePaths::fromArray([
                'child1' => NodeAggregateId::fromString('child-1'),
                'child2' => NodeAggregateId::fromString('child-2'),
                'child1/grandchild1' => NodeAggregateId::fromString('grandchild-1-1'),
                'child1/grandchild2' => NodeAggregateId::fromString('grandchild-1-2'),
                'child2/grandchild1' => NodeAggregateId::fromString('grandchild-2-1'),
                'child2/grandchild2' => NodeAggregateId::fromString('grandchild-2-1'),
            ]),
            'expectedNodeAggregateIdsByPath' => [
                'child1' => NodeAggregateId::fromString('child-1'),
                'child2' => NodeAggregateId::fromString('child-2'),
                'child1/grandchild1' => NodeAggregateId::fromString('grandchild-1-1'),
                'child1/grandchild2' => NodeAggregateId::fromString('grandchild-1-2'),
                'child2/grandchild1' => NodeAggregateId::fromString('grandchild-2-1'),
                'child2/grandchild2' => NodeAggregateId::fromString('grandchild-2-1'),
            ]
        ];
    }
}
