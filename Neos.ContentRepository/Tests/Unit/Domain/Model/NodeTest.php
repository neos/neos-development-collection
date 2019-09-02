<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeTemplate;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Cache\FirstLevelNodeCache;
use Neos\ContentRepository\Domain\Service\Context;

/**
 * Testcase for the "Node" domain model
 */
class NodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createNodeFromTemplateUsesWorkspaceFromContextForNodeData()
    {
        $workspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $parentNodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $newNodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();

        $mockFirstLevelNodeCache = $this->createMock(FirstLevelNodeCache::class);
        $newNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $context->expects(self::any())->method('getFirstLevelNodeCache')->will(self::returnValue($mockFirstLevelNodeCache));
        $nodeTemplate = new NodeTemplate();

        $context->expects(self::any())->method('getWorkspace')->will(self::returnValue($workspace));

        $nodeFactory = $this->createMock(NodeFactory::class);

        $parentNode = new Node($parentNodeData, $context);

        $this->inject($parentNode, 'nodeFactory', $nodeFactory);

        $parentNodeData->expects(self::atLeastOnce())->method('createNodeDataFromTemplate')->with($nodeTemplate, 'bar', $workspace)->will(self::returnValue($newNodeData));
        $nodeFactory->expects(self::atLeastOnce())->method('createFromNodeData')->with($newNodeData, $context)->will(self::returnValue($newNode));

        $parentNode->createNodeFromTemplate($nodeTemplate, 'bar');
    }


    /**
     * @test
     */
    public function getPrimaryChildNodeReturnsTheFirstChildNode()
    {
        $mockNodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData->expects(self::any())->method('getPath')->will(self::returnValue('/foo/bar'));
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();


        $node = new Node($mockNodeData, $mockContext);

        $mockNodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->getMock();
        $this->inject($node, 'nodeDataRepository', $mockNodeDataRepository);

        $expectedNode = $this->createMock(NodeInterface::class);
        $mockNodeDataRepository->expects(self::once())->method('findFirstByParentAndNodeTypeInContext')->with('/foo/bar', null, $mockContext)->will(self::returnValue($expectedNode));

        $primaryChildNode = $node->getPrimaryChildNode();

        self::assertSame($expectedNode, $primaryChildNode);
    }

    /**
     * Data Provider for contextPathPatternShouldWorkWithContexts
     *
     * @return array
     */
    public function dataSourceForContextPathPattern()
    {
        return [
            'empty node path' => [
                'path' => '',
                'expected' => [
                ]
            ],
            'node path starting with /' => [
                'path' => '/features',
                'expected' => [
                    0 => '/features',
                    'NodePath' => '/features',
                    1 => '/features'
                ]
            ],
            'simple context with no workspace' => [
                'path' => 'features',
                'expected' => [
                    0 => 'features',
                    'NodePath' => 'features',
                    1 => 'features'
                ]
            ],
            'simple context with workspace' => [
                'path' => 'features@user-admin',
                'expected' => [
                    0 => 'features@user-admin',
                    'NodePath' => 'features',
                    1 => 'features',
                    'WorkspaceName' => 'user-admin',
                    2 => 'user-admin'
                ]
            ],
            'simple dimension' => [
                'path' => 'features@user-admin;language=de_DE,mul_ZZ',
                'expected' => [
                    0 => 'features@user-admin;language=de_DE,mul_ZZ',
                    'NodePath' => 'features',
                    1 => 'features',
                    'WorkspaceName' => 'user-admin',
                    2 => 'user-admin',
                    'Dimensions' => 'language=de_DE,mul_ZZ',
                    3 => 'language=de_DE,mul_ZZ'
                ]
            ],
            'multiple dimensions' => [
                'path' => 'features@user-admin;language=de_DE,mul_ZZ&original=blah',
                'expected' => [
                    0 => 'features@user-admin;language=de_DE,mul_ZZ&original=blah',
                    'NodePath' => 'features',
                    1 => 'features',
                    'WorkspaceName' => 'user-admin',
                    2 => 'user-admin',
                    'Dimensions' => 'language=de_DE,mul_ZZ&original=blah',
                    3 => 'language=de_DE,mul_ZZ&original=blah'
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider dataSourceForContextPathPattern
     */
    public function contextPathPatternShouldWorkWithContexts($path, $expected)
    {
        $matches = [];
        preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $path, $matches);

        self::assertSame($expected, $matches);
    }

    /**
     * @return array
     */
    public function dataSourceForInvalidContextPaths()
    {
        return [
            'invalid dimension values' => [
                'path' => 'features@user-admin;language=de_DE,mul_ZZ=something'
            ],
            'superfluous separator with more data' => [
                'path' => 'features@user-admin;language=de_DE;mul_ZZ=multilanguage'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider dataSourceForInvalidContextPaths
     */
    public function contextPathPatternShouldNotMatchOnInvalidPaths($path)
    {
        $result = preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $path, $matches);
        self::assertEquals(0, $result, 'The invalid context path yielded matches: ' . print_r($matches, true));
    }

    /**
     * @test
     */
    public function createNodeWithAutoCreatedChildNodesAndNoIdentifierUsesGeneratedIdentifierOfNodeForChildNodes()
    {
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $mockContext->expects(self::any())->method('getTargetDimensions')->will(self::returnValue(['language' => 'mul_ZZ']));
        $mockFirstLevelNodeCache = $this->createMock(FirstLevelNodeCache::class);
        $mockContext->expects(self::any())->method('getFirstLevelNodeCache')->will(self::returnValue($mockFirstLevelNodeCache));

        $mockNodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockSubNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();

        $mockNodeType->expects(self::any())->method('getDefaultValuesForProperties')->will(self::returnValue([]));
        $mockNodeType->expects(self::any())->method('getAutoCreatedChildNodes')->will(self::returnValue([
            'subnode1' => $mockSubNodeType
        ]));

        $i = 0;
        $generatedIdentifiers = [];
        $node = $this->getMockBuilder(Node::class)->setMethods(['createSingleNode'])->setConstructorArgs([$mockNodeData, $mockContext])->getMock();
        $node->expects(self::any())->method('createSingleNode')->will(self::returnCallback(function () use (&$i, &$generatedIdentifiers, $mockSubNodeType) {
            $newNode = $this->createMock(NodeInterface::class);
            $newNode->expects(self::any())->method('getIdentifier')->will(self::returnValue('node-' . $i++));

            $newNode->expects(self::once())->method('createNode')->with('subnode1', $mockSubNodeType, $this->callback(function ($identifier) use (&$generatedIdentifiers, $i) {
                $generatedIdentifiers[$i] = $identifier;
                return true;
            }));

            return $newNode;
        }));

        $node->createNode('foo', $mockNodeType);
        $node->createNode('bar', $mockNodeType);

        self::assertNotSame($generatedIdentifiers[1], $generatedIdentifiers[2], 'Child nodes should have distinct identifiers');
    }
}
