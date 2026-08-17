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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
    private const WORKSPACE_NAME = 'live';

    /**
     * Data providers have to be static and therefore cannot build mocks. Instead they
     * describe the needed objects with the sentinel arrays understood by this helper,
     * and the test methods turn those descriptions into actual mocks.
     *
     * @param mixed $specification
     * @return mixed
     */
    private function materialize($specification)
    {
        if (!is_array($specification)) {
            return $specification;
        }
        if (isset($specification['__nodeType'])) {
            $nodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
            $nodeType->expects(self::any())->method('getName')->willReturn($specification['__nodeType']);
            return $nodeType;
        }
        if (isset($specification['__node'])) {
            return $this->mockNode($specification['__node']);
        }
        if (isset($specification['__arrayObject'])) {
            return new \ArrayObject($this->materialize($specification['__arrayObject']));
        }
        return array_map(fn ($item) => $this->materialize($item), $specification);
    }

    /**
     * Builds a node mock with the given identifier, living in the "live" workspace.
     */
    private function mockNode(string $nodeIdentifier): NodeInterface
    {
        $workspaceMock = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $workspaceMock->expects(self::any())->method('getName')->willReturn(self::WORKSPACE_NAME);

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->expects(self::any())->method('getWorkspace')->willReturn($workspaceMock);

        $node = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();
        $node->expects(self::any())->method('getContext')->willReturn($contextMock);
        $node->expects(self::any())->method('getIdentifier')->willReturn($nodeIdentifier);

        return $node;
    }

    /**
     * Provides datasets for testing the CachingHelper::nodeTypeTag method.
     *
     * @return array
     */
    public static function nodeTypeTagDataProvider()
    {
        $nodeTypeName1 = 'Neos.Neos:Foo';
        $nodeTypeName2 = 'Neos.Neos:Bar';
        $nodeTypeName3 = 'Neos.Neos:Moo';

        $nodeTypeObject1 = ['__nodeType' => $nodeTypeName1];
        $nodeTypeObject2 = ['__nodeType' => $nodeTypeName2];
        $nodeTypeObject3 = ['__nodeType' => $nodeTypeName3];

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
            [['__arrayObject' => [$nodeTypeObject1, $nodeTypeObject2, $nodeTypeObject3]],
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
     * @param mixed $input
     * @param array $expectedResult
     */
    #[DataProvider('nodeTypeTagDataProvider')]
    #[Test]
    public function nodeTypeTagProvidesExpectedResult($input, $expectedResult)
    {
        $helper = new CachingHelper();
        $actualResult = $helper->nodeTypeTag($this->materialize($input));
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provides datasets for testing the CachingHelper::nodeTypeTag method with an context node.
     *
     * @return array
     */
    public static function nodeTypeTagWithContextNodeDataProvider()
    {
        $cacheHelper = new CachingHelper();

        $contextNode = ['__node' => 'ca511a55-c5c0-f7d7-8d71-8edeffc75306'];

        $hashedWorkspaceName = $cacheHelper->renderWorkspaceTagForContextNode(self::WORKSPACE_NAME);

        $nodeTypeName1 = 'Neos.Neos:Foo';
        $nodeTypeName2 = 'Neos.Neos:Bar';
        $nodeTypeName3 = 'Neos.Neos:Moo';

        $nodeTypeObject1 = ['__nodeType' => $nodeTypeName1];
        $nodeTypeObject2 = ['__nodeType' => $nodeTypeName2];
        $nodeTypeObject3 = ['__nodeType' => $nodeTypeName3];

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
            [['__arrayObject' => [$nodeTypeObject1, $nodeTypeObject2, $nodeTypeObject3]], $contextNode,
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
     *
     * @param $input
     * @param $contextNode
     * @param $expectedResult
     */
    #[DataProvider('nodeTypeTagWithContextNodeDataProvider')]
    #[Test]
    public function nodeTypeTagRespectsContextNodesWorkspace($input, $contextNode, $expectedResult)
    {
        $helper = new CachingHelper();
        $actualResult = $helper->nodeTypeTag($this->materialize($input), $this->materialize($contextNode));
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     *
     */
    public static function nodeDataProvider()
    {
        $cachingHelper = new CachingHelper();

        $nodeIdentifier = 'ca511a55-c5c0-f7d7-8d71-8edeffc75306';
        $node = ['__node' => $nodeIdentifier];

        $anotherNodeIdentifier = '7005c7cf-4d19-ce36-0873-476b6cadb71a';
        $anotherNode = ['__node' => $anotherNodeIdentifier];

        $hashedWorkspaceName = $cachingHelper->renderWorkspaceTagForContextNode(self::WORKSPACE_NAME);

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
     * @param $nodes
     * @param $expectedResult
     */
    #[DataProvider('nodeDataProvider')]
    #[Test]
    public function nodeTagsAreSetupWithWorkspaceAndIdentifier($nodes, $expectedResult)
    {
        $helper = new CachingHelper();
        $actualResult = $helper->nodeTag($this->materialize($nodes));
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
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

    #[Test]
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
    public static function descendantOfDataProvider()
    {
        $cachingHelper = new CachingHelper();

        $nodeIdentifier = 'ca511a55-c5c0-f7d7-8d71-8edeffc75306';
        $node = ['__node' => $nodeIdentifier];

        $anotherNodeIdentifier = '7005c7cf-4d19-ce36-0873-476b6cadb71a';
        $anotherNode = ['__node' => $anotherNodeIdentifier];

        $hashedWorkspaceName = $cachingHelper->renderWorkspaceTagForContextNode(self::WORKSPACE_NAME);

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
     * @param $nodes
     * @param $expectedResult
     */
    #[DataProvider('descendantOfDataProvider')]
    #[Test]
    public function descendantOfTagsAreSetupWithWorkspaceAndIdentifier($nodes, $expectedResult)
    {
        $helper = new CachingHelper();
        $actualResult = $helper->descendantOfTag($this->materialize($nodes));
        self::assertEquals($expectedResult, $actualResult);
    }
}
