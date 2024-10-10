<?php

namespace Neos\Neos\Tests\Unit\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Fusion\Helper\CachingHelper;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests the CachingHelper
 */
class CachingHelperTest extends UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Provides datasets for testing the CachingHelper::nodeTypeTag method.
     *
     * @return array
     */
    public function nodeTypeTagDataProvider()
    {
        $nodeTypeName1 = 'Neos.Neos:Foo';
        $nodeTypeName2 = 'Neos.Neos:Bar';
        $nodeTypeName3 = 'Neos.Neos:Moo';

        return [
            [$nodeTypeName1,
                [
                    'NodeType_364cfc8e70b2baa23dbd14503d2bd00e063829e7_Neos_Neos-Foo',
                    'NodeType_7505d64a54e061b7acd54ccd58b49dc43500b635_Neos_Neos-Foo',
                    'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
                ]
            ],
            [[$nodeTypeName1, $nodeTypeName2, $nodeTypeName3],
                [
                    'NodeType_364cfc8e70b2baa23dbd14503d2bd00e063829e7_Neos_Neos-Foo',
                    'NodeType_364cfc8e70b2baa23dbd14503d2bd00e063829e7_Neos_Neos-Bar',
                    'NodeType_364cfc8e70b2baa23dbd14503d2bd00e063829e7_Neos_Neos-Moo',
                    'NodeType_7505d64a54e061b7acd54ccd58b49dc43500b635_Neos_Neos-Foo',
                    'NodeType_7505d64a54e061b7acd54ccd58b49dc43500b635_Neos_Neos-Bar',
                    'NodeType_7505d64a54e061b7acd54ccd58b49dc43500b635_Neos_Neos-Moo',
                    'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
                ]
            ],
            [(new \ArrayObject([$nodeTypeName1, $nodeTypeName2, $nodeTypeName3])),
                [
                    'NodeType_364cfc8e70b2baa23dbd14503d2bd00e063829e7_Neos_Neos-Foo',
                    'NodeType_364cfc8e70b2baa23dbd14503d2bd00e063829e7_Neos_Neos-Bar',
                    'NodeType_364cfc8e70b2baa23dbd14503d2bd00e063829e7_Neos_Neos-Moo',
                    'NodeType_7505d64a54e061b7acd54ccd58b49dc43500b635_Neos_Neos-Foo',
                    'NodeType_7505d64a54e061b7acd54ccd58b49dc43500b635_Neos_Neos-Bar',
                    'NodeType_7505d64a54e061b7acd54ccd58b49dc43500b635_Neos_Neos-Moo',
                    'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider nodeTypeTagDataProvider
     *
     * @param mixed $input
     * @param array $expectedResult
     */
    public function nodeTypeTagProvidesExpectedResult($input, $expectedResult)
    {
        $contextNode = $this->createNode(NodeAggregateId::fromString("na"));

        $helper = new CachingHelper();
        $actualResult = $helper->nodeTypeTag($input, $contextNode);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     *
     */
    public function nodeDataProvider()
    {
        $nodeIdentifier1 = 'ca511a55-c5c0-f7d7-8d71-8edeffc75306';
        $node1 = $this->createNode(NodeAggregateId::fromString($nodeIdentifier1));

        $nodeIdentifier2 = '7005c7cf-4d19-ce36-0873-476b6cadb71a';
        $node2 = $this->createNode(NodeAggregateId::fromString($nodeIdentifier2));

        return [
            [$node1, [
                'Node_364cfc8e70b2baa23dbd14503d2bd00e063829e7_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'Node_7505d64a54e061b7acd54ccd58b49dc43500b635_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
            ]],
            [[$node1], [
                'Node_364cfc8e70b2baa23dbd14503d2bd00e063829e7_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'Node_7505d64a54e061b7acd54ccd58b49dc43500b635_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
            ]],
            [[$node1, $node2], [
                'Node_364cfc8e70b2baa23dbd14503d2bd00e063829e7_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'Node_364cfc8e70b2baa23dbd14503d2bd00e063829e7_7005c7cf-4d19-ce36-0873-476b6cadb71a',
                'Node_7505d64a54e061b7acd54ccd58b49dc43500b635_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'Node_7505d64a54e061b7acd54ccd58b49dc43500b635_7005c7cf-4d19-ce36-0873-476b6cadb71a',
                'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
            ]],
            [(new \ArrayObject([$node1, $node2])), [
                'Node_364cfc8e70b2baa23dbd14503d2bd00e063829e7_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'Node_364cfc8e70b2baa23dbd14503d2bd00e063829e7_7005c7cf-4d19-ce36-0873-476b6cadb71a',
                'Node_7505d64a54e061b7acd54ccd58b49dc43500b635_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'Node_7505d64a54e061b7acd54ccd58b49dc43500b635_7005c7cf-4d19-ce36-0873-476b6cadb71a',
                'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
            ]]
        ];
    }

    /**
     * @test
     * @dataProvider nodeDataProvider
     *
     * @param $nodes
     * @param $expectedResult
     */
    public function nodeTagsAreSetupWithWorkspaceAndIdentifier($nodes, $expectedResult)
    {
        $helper = new CachingHelper();
        $actualResult = $helper->nodeTag($nodes);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function nodeTagsCanBeInitializedWithAnIdentifierString()
    {
        $helper = new CachingHelper();

        $nodeIdentifier = 'ca511a55-c5c0-f7d7-8d71-8edeffc75306';
        $node = $this->createNode(NodeAggregateId::fromString($nodeIdentifier));

        $actual = $helper->nodeTagForIdentifier($nodeIdentifier, $node);

        self::assertEquals([
            'Node_364cfc8e70b2baa23dbd14503d2bd00e063829e7_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
            'Node_7505d64a54e061b7acd54ccd58b49dc43500b635_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
            'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
        ], $actual);
    }

    /**
     * @test
     */
    public function nodeTagForIdentifierStringWillFallbackToLegacyTagIfNoContextNodeIsGiven()
    {
        $helper = new CachingHelper();
        $identifier = 'some-uuid-identifier';

        $contextNode = $this->createNode(NodeAggregateId::fromString("na"));

        $actual = $helper->nodeTagForIdentifier($identifier, $contextNode);
        self::assertEquals([
            'Node_364cfc8e70b2baa23dbd14503d2bd00e063829e7_some-uuid-identifier',
            'Node_7505d64a54e061b7acd54ccd58b49dc43500b635_some-uuid-identifier',
            'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
        ], $actual);
    }

    public function descendantOfDataProvider()
    {
        $nodeIdentifier1 = 'ca511a55-c5c0-f7d7-8d71-8edeffc75306';
        $node1 = $this->createNode(NodeAggregateId::fromString($nodeIdentifier1));

        $nodeIdentifier2 = '7005c7cf-4d19-ce36-0873-476b6cadb71a';
        $node2 = $this->createNode(NodeAggregateId::fromString($nodeIdentifier2));


        return [
            [$node1, [
                'DescendantOf_364cfc8e70b2baa23dbd14503d2bd00e063829e7_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'DescendantOf_7505d64a54e061b7acd54ccd58b49dc43500b635_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
            ]],
            [[$node1], [
                'DescendantOf_364cfc8e70b2baa23dbd14503d2bd00e063829e7_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'DescendantOf_7505d64a54e061b7acd54ccd58b49dc43500b635_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
            ]],
            [[$node1, $node2], [
                'DescendantOf_364cfc8e70b2baa23dbd14503d2bd00e063829e7_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'DescendantOf_364cfc8e70b2baa23dbd14503d2bd00e063829e7_7005c7cf-4d19-ce36-0873-476b6cadb71a',
                'DescendantOf_7505d64a54e061b7acd54ccd58b49dc43500b635_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'DescendantOf_7505d64a54e061b7acd54ccd58b49dc43500b635_7005c7cf-4d19-ce36-0873-476b6cadb71a',
                'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
            ]],
            [(new \ArrayObject([$node1, $node2])), [
                'DescendantOf_364cfc8e70b2baa23dbd14503d2bd00e063829e7_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'DescendantOf_364cfc8e70b2baa23dbd14503d2bd00e063829e7_7005c7cf-4d19-ce36-0873-476b6cadb71a',
                'DescendantOf_7505d64a54e061b7acd54ccd58b49dc43500b635_ca511a55-c5c0-f7d7-8d71-8edeffc75306',
                'DescendantOf_7505d64a54e061b7acd54ccd58b49dc43500b635_7005c7cf-4d19-ce36-0873-476b6cadb71a',
                'Workspace_364cfc8e70b2baa23dbd14503d2bd00e063829e7',
            ]]
        ];
    }

    /**
     * @test
     * @dataProvider descendantOfDataProvider
     *
     * @param $nodes
     * @param $expectedResult
     */
    public function descendantOfTagsAreSetupWithWorkspaceAndIdentifier($nodes, $expectedResult)
    {
        $helper = new CachingHelper();
        $actualResult = $helper->descendantOfTag($nodes);
        self::assertEquals($expectedResult, $actualResult);
    }

    private function createNode(NodeAggregateId $nodeAggregateId): Node
    {
        $now = new \DateTimeImmutable();
        return Node::create(
            ContentRepositoryId::fromString("default"),
            WorkspaceName::forLive(),
            DimensionSpacePoint::createWithoutDimensions(),
            $nodeAggregateId,
            OriginDimensionSpacePoint::createWithoutDimensions(),
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
            NodeTypeName::fromString("SomeNodeTypeName"),
            new PropertyCollection(SerializedPropertyValues::fromArray([]), new PropertyConverter(new Serializer([], []))),
            null,
            NodeTags::createEmpty(),
            Timestamps::create($now, $now, null, null),
            VisibilityConstraints::withoutRestrictions(),
        );
    }
}
