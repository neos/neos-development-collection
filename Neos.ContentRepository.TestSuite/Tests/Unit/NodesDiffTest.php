<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Tests\Unit;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\NodeDiff;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\NodesDiff;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\SampleNodeFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class NodesDiffTest extends TestCase
{
    /**
     * @dataProvider nodesComparisonProvider
     */
    public function testFromNodesComparison(Nodes $referenceNodes, Nodes $nodesToCompare, ?NodesDiff $expectedNodesDiff): void
    {
        Assert::assertEquals(
            $expectedNodesDiff,
            NodesDiff::tryFromNodesComparison($nodesToCompare, $referenceNodes, null)
        );
    }

    public static function nodesComparisonProvider(): iterable
    {
        $referenceNode = SampleNodeFactory::createSampleNode();

        yield 'emptyNodes' => [
            'referenceNodes' => Nodes::createEmpty(),
            'nodesToCompares' => Nodes::createEmpty(),
            'expectedNodesDiff' => null,
        ];

        yield 'identicalNodes' => [
            'referenceNodes' => Nodes::fromArray([$referenceNode]),
            'nodesToCompares' => Nodes::fromArray([$referenceNode]),
            'expectedNodesDiff' => null,
        ];

        $anotherNode = SampleNodeFactory::modifyNodeWith(
            node: $referenceNode,
            aggregateId: NodeAggregateId::fromString('another'),
        );

        yield 'onlyMovedNodes' => [
            'referenceNodes' => Nodes::fromArray([$referenceNode, $anotherNode]),
            'nodesToCompares' => Nodes::fromArray([$anotherNode, $referenceNode]),
            'expectedNodesDiff' => NodesDiff::tryCreate(
                nodes: [
                    $anotherNode->aggregateId,
                    $referenceNode->aggregateId,
                ],
            ),
        ];

        $nodeToModify = SampleNodeFactory::modifyNodeWith(
            node: $referenceNode,
            aggregateId: NodeAggregateId::fromString('modify'),
        );

        yield 'onlyDifferingNodes' => [
            'referenceNodes' => Nodes::fromArray([
                $referenceNode,
                $anotherNode,
                $nodeToModify,
            ]),
            'nodesToCompare' => Nodes::fromArray([
                $anotherNode,
                $referenceNode,
                SampleNodeFactory::modifyNodeWith(
                    node: $nodeToModify,
                    nodeTypeName: NodeTypeName::fromString('Neos.ContentRepositry:OtherTesting'),
                ),
            ]),
            'expectedNodesDiff' => NodesDiff::tryCreate(
                nodes: [
                    NodeAggregateId::fromString('another'),
                    NodeAggregateId::fromString('nody-mc-nodeface'),
                    NodeDiff::tryCreate(
                        discriminator: $nodeToModify->aggregateId,
                        nodeTypeName: NodeTypeName::fromString('Neos.ContentRepositry:OtherTesting'),
                    )
                ],
            )
        ];

        $nodeToRemove = SampleNodeFactory::modifyNodeWith(
            node: $referenceNode,
            aggregateId: NodeAggregateId::fromString('remove'),
        );

        yield 'differingAndRemovedNodes' => [
            'referenceNodes' => Nodes::fromArray([
                $referenceNode,
                $anotherNode,
                $nodeToRemove,
                $nodeToModify,
            ]),
            'nodesToCompare' => Nodes::fromArray([
                $anotherNode,
                $referenceNode,
                SampleNodeFactory::modifyNodeWith(
                    node: $nodeToModify,
                    nodeTypeName: NodeTypeName::fromString('Neos.ContentRepositry:OtherTesting'),
                ),
            ]),
            'expectedNodesDiff' => NodesDiff::tryCreate(
                nodes: [
                    NodeAggregateId::fromString('another'),
                    NodeAggregateId::fromString('nody-mc-nodeface'),
                    NodeDiff::tryCreate(
                        discriminator: $nodeToModify->aggregateId,
                        nodeTypeName: NodeTypeName::fromString('Neos.ContentRepositry:OtherTesting'),
                    )
                ],
                removedNodes: NodeAggregateIds::fromArray([NodeAggregateId::fromString('remove')]),
            )
        ];
    }
}
