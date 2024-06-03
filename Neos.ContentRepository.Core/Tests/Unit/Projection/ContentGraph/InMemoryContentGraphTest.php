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

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use PHPUnit\Framework\TestCase;

class InMemoryContentGraphTest extends TestCase
{
    /**
     * @dataProvider patternMatchProvider
     */
    public function testToMinimalConstitutingEvents(
        string $serializedPath,
        bool $expectedResult
    ) {
        self::assertSame($expectedResult, AbsoluteNodePath::patternIsMatchedByString($serializedPath));
    }

    public static function ContentGraphProvider(): \Traversable
    {
        $contentRepositoryId = ContentRepositoryId::fromString('default');
        $workspaceName = WorkspaceName::forLive();

        yield 'onlyRootExample' => [
            'contentGraph' => new InMemoryContentGraph(
                $workspaceName,
                ContentStreamId::fromString('cs-id'),
                NodeAggregates::fromArray([
                    NodeAggregate::create(
                        $contentRepositoryId,
                        $workspaceName,
                        NodeAggregateId::fromString('lady-eleonode-rootford'),
                        NodeAggregateClassification::CLASSIFICATION_ROOT,
                        NodeTypeName::fromString('Neos.ContentRepository:Root'),
                        null,
                        new OriginDimensionSpacePointSet([
                            OriginDimensionSpacePoint::fromArray([
                                'example' => 'general'
                            ]),
                            OriginDimensionSpacePoint::fromArray([
                                'example' => 'peer'
                            ]),
                        ]),
                        ''
                    )
                ])
            )
        ];

    }
}
