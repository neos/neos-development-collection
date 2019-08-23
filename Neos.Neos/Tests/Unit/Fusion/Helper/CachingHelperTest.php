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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Fusion\Helper\CachingHelper;

/**
 * Tests the CachingHelper
 */
class CachingHelperTest extends UnitTestCase
{
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

        $nodeTypeObject1 = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeTypeObject1->expects(self::any())->method('getName')->willReturn($nodeTypeName1);

        $nodeTypeObject2 = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeTypeObject2->expects(self::any())->method('getName')->willReturn($nodeTypeName2);

        $nodeTypeObject3 = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeTypeObject3->expects(self::any())->method('getName')->willReturn($nodeTypeName3);

        return [
            [$nodeTypeName1, 'NodeType_' . $nodeTypeName1],
            [[$nodeTypeName1, $nodeTypeName2, $nodeTypeName3],
                [
                    'NodeType_' . $nodeTypeName1,
                    'NodeType_' . $nodeTypeName2,
                    'NodeType_' . $nodeTypeName3
                ]
            ],
            [$nodeTypeObject1, 'NodeType_' . $nodeTypeName1],
            [[$nodeTypeName1, $nodeTypeObject2, $nodeTypeObject3],
                [
                    'NodeType_' . $nodeTypeName1,
                    'NodeType_' . $nodeTypeName2,
                    'NodeType_' . $nodeTypeName3
                ]
            ],
            [(new \ArrayObject([$nodeTypeObject1, $nodeTypeObject2, $nodeTypeObject3])),
                [
                    'NodeType_' . $nodeTypeName1,
                    'NodeType_' . $nodeTypeName2,
                    'NodeType_' . $nodeTypeName3
                ]
            ],
            [(object)['stdClass' => 'will do nothing'], '']
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
        $helper = new CachingHelper();
        $actualResult = $helper->nodeTypeTag($input);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provides datasets for testing the CachingHelper::nodeTypeTag method with an context node.
     *
     * @return array
     */
    public function nodeTypeTagWithContextNodeDataProvider()
    {
        $cacheHelper = new CachingHelper();

        $workspaceName = 'live';
        $workspaceMock = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $workspaceMock->expects(self::any())->method('getName')->willReturn($workspaceName);

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->expects(self::any())->method('getWorkspace')->willReturn($workspaceMock);

        $contextNode = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();
        $contextNode->expects(self::any())->method('getContext')->willReturn($contextMock);

        $hashedWorkspaceName = $cacheHelper->renderWorkspaceTagForContextNode($workspaceName);

        $nodeTypeName1 = 'Neos.Neos:Foo';
        $nodeTypeName2 = 'Neos.Neos:Bar';
        $nodeTypeName3 = 'Neos.Neos:Moo';

        $nodeTypeObject1 = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeTypeObject1->expects(self::any())->method('getName')->willReturn($nodeTypeName1);

        $nodeTypeObject2 = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeTypeObject2->expects(self::any())->method('getName')->willReturn($nodeTypeName2);

        $nodeTypeObject3 = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $nodeTypeObject3->expects(self::any())->method('getName')->willReturn($nodeTypeName3);

        return [
            [$nodeTypeName1, $contextNode, 'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName1],
            [[$nodeTypeName1, $nodeTypeName2, $nodeTypeName3], $contextNode,
                [
                    'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName1,
                    'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName2,
                    'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName3
                ]
            ],
            [$nodeTypeObject1, $contextNode, 'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName1],
            [[$nodeTypeName1, $nodeTypeObject2, $nodeTypeObject3], $contextNode,
                [
                    'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName1,
                    'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName2,
                    'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName3
                ]
            ],
            [(new \ArrayObject([$nodeTypeObject1, $nodeTypeObject2, $nodeTypeObject3])), $contextNode,
                [
                    'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName1,
                    'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName2,
                    'NodeType_'.$hashedWorkspaceName.'_' . $nodeTypeName3
                ]
            ],
            [(object)['stdClass' => 'will do nothing'], $contextNode, '']
        ];
    }

    /**
     * @test
     * @dataProvider nodeTypeTagWithContextNodeDataProvider
     *
     * @param $input
     * @param $contextNode
     * @param $expectedResult
     */
    public function nodeTypeTagRespectsContextNodesWorkspace($input, $contextNode, $expectedResult)
    {
        $helper = new CachingHelper();
        $actualResult = $helper->nodeTypeTag($input, $contextNode);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     *
     */
    public function nodeDataProvider()
    {
        $cachingHelper = new CachingHelper();

        $workspaceName = 'live';
        $workspaceMock = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $workspaceMock->expects(self::any())->method('getName')->willReturn($workspaceName);

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->expects(self::any())->method('getWorkspace')->willReturn($workspaceMock);

        $nodeIdentifier = 'ca511a55-c5c0-f7d7-8d71-8edeffc75306';
        $node = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();
        $node->expects(self::any())->method('getContext')->willReturn($contextMock);
        $node->expects(self::any())->method('getIdentifier')->willReturn($nodeIdentifier);

        $anotherNodeIdentifier = '7005c7cf-4d19-ce36-0873-476b6cadb71a';
        $anotherNode = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();
        $anotherNode->expects(self::any())->method('getContext')->willReturn($contextMock);
        $anotherNode->expects(self::any())->method('getIdentifier')->willReturn($anotherNodeIdentifier);

        $hashedWorkspaceName = $cachingHelper->renderWorkspaceTagForContextNode($workspaceName);

        return [
            [$node, ['Node_' . $hashedWorkspaceName.'_'.$nodeIdentifier]],
            [[$node], ['Node_' . $hashedWorkspaceName.'_'.$nodeIdentifier]],
            [[$node, $anotherNode], [
                'Node_' . $hashedWorkspaceName.'_'.$nodeIdentifier,
                'Node_' . $hashedWorkspaceName.'_'.$anotherNodeIdentifier
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

        $workspaceName = 'live';
        $workspaceMock = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $workspaceMock->expects(self::any())->method('getName')->willReturn($workspaceName);

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->expects(self::any())->method('getWorkspace')->willReturn($workspaceMock);

        $nodeIdentifier = 'ca511a55-c5c0-f7d7-8d71-8edeffc75306';
        $node = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();
        $node->expects(self::any())->method('getContext')->willReturn($contextMock);
        $node->expects(self::any())->method('getIdentifier')->willReturn($nodeIdentifier);

        $hashedWorkspaceName = $helper->renderWorkspaceTagForContextNode($workspaceName);

        $actual = $helper->nodeTagForIdentifier($nodeIdentifier, $node);

        self::assertEquals('Node_'.$hashedWorkspaceName.'_'.$nodeIdentifier, $actual);
    }

    /**
     * @test
     */
    public function nodeTagForIdentifierStringWillFallbackToLegacyTagIfNoContextNodeIsGiven()
    {
        $helper = new CachingHelper();
        $identifier = 'some-uuid-identifier';

        $actual = $helper->nodeTagForIdentifier($identifier);
        self::assertEquals('Node_'.$identifier, $actual);
    }

    /**
     *
     */
    public function descendantOfDataProvider()
    {
        $cachingHelper = new CachingHelper();

        $workspaceName = 'live';
        $workspaceMock = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $workspaceMock->expects(self::any())->method('getName')->willReturn($workspaceName);

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->expects(self::any())->method('getWorkspace')->willReturn($workspaceMock);

        $nodeIdentifier = 'ca511a55-c5c0-f7d7-8d71-8edeffc75306';
        $node = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();
        $node->expects(self::any())->method('getContext')->willReturn($contextMock);
        $node->expects(self::any())->method('getIdentifier')->willReturn($nodeIdentifier);

        $anotherNodeIdentifier = '7005c7cf-4d19-ce36-0873-476b6cadb71a';
        $anotherNode = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();
        $anotherNode->expects(self::any())->method('getContext')->willReturn($contextMock);
        $anotherNode->expects(self::any())->method('getIdentifier')->willReturn($anotherNodeIdentifier);

        $hashedWorkspaceName = $cachingHelper->renderWorkspaceTagForContextNode($workspaceName);

        return [
            [$node, ['DescendantOf_' . $hashedWorkspaceName.'_'.$nodeIdentifier]],
            [[$node], ['DescendantOf_' . $hashedWorkspaceName.'_'.$nodeIdentifier]],
            [[$node, $anotherNode], [
                'DescendantOf_' . $hashedWorkspaceName.'_'.$nodeIdentifier,
                'DescendantOf_' . $hashedWorkspaceName.'_'.$anotherNodeIdentifier
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
}
