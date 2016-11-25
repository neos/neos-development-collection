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
        $context->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));
        $nodeTemplate = new NodeTemplate();

        $context->expects($this->any())->method('getWorkspace')->will($this->returnValue($workspace));

        $nodeFactory = $this->createMock(NodeFactory::class);

        $parentNode = new Node($parentNodeData, $context);

        $this->inject($parentNode, 'nodeFactory', $nodeFactory);

        $parentNodeData->expects($this->atLeastOnce())->method('createNodeDataFromTemplate')->with($nodeTemplate, 'bar', $workspace)->will($this->returnValue($newNodeData));
        $nodeFactory->expects($this->atLeastOnce())->method('createFromNodeData')->with($newNodeData, $context)->will($this->returnValue($newNode));

        $parentNode->createNodeFromTemplate($nodeTemplate, 'bar');
    }


    /**
     * @test
     */
    public function getPrimaryChildNodeReturnsTheFirstChildNode()
    {
        $mockNodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeData->expects($this->any())->method('getPath')->will($this->returnValue('/foo/bar'));
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();


        $node = new Node($mockNodeData, $mockContext);

        $mockNodeDataRepository = $this->getMockBuilder(NodeDataRepository::class)->disableOriginalConstructor()->getMock();
        $this->inject($node, 'nodeDataRepository', $mockNodeDataRepository);

        $expectedNode = $this->createMock(NodeInterface::class);
        $mockNodeDataRepository->expects($this->once())->method('findFirstByParentAndNodeTypeInContext')->with('/foo/bar', null, $mockContext)->will($this->returnValue($expectedNode));

        $primaryChildNode = $node->getPrimaryChildNode();

        $this->assertSame($expectedNode, $primaryChildNode);
    }

    /**
     * Data Provider for contextPathPatternShouldWorkWithContexts
     *
     * @return array
     */
    public function dataSourceForContextPathPattern()
    {
        return array(
            'empty node path' => array(
                'path' => '',
                'expected' => array(
                )
            ),
            'node path starting with /' => array(
                'path' => '/features',
                'expected' => array(
                    0 => '/features',
                    'NodePath' => '/features',
                    1 => '/features'
                )
            ),
            'simple context with no workspace' => array(
                'path' => 'features',
                'expected' => array(
                    0 => 'features',
                    'NodePath' => 'features',
                    1 => 'features'
                )
            ),
            'simple context with workspace' => array(
                'path' => 'features@user-admin',
                'expected' => array(
                    0 => 'features@user-admin',
                    'NodePath' => 'features',
                    1 => 'features',
                    'WorkspaceName' => 'user-admin',
                    2 => 'user-admin'
                )
            ),
            'simple dimension' => array(
                'path' => 'features@user-admin;language=de_DE,mul_ZZ',
                'expected' => array(
                    0 => 'features@user-admin;language=de_DE,mul_ZZ',
                    'NodePath' => 'features',
                    1 => 'features',
                    'WorkspaceName' => 'user-admin',
                    2 => 'user-admin',
                    'Dimensions' => 'language=de_DE,mul_ZZ',
                    3 => 'language=de_DE,mul_ZZ'
                )
            ),
            'multiple dimensions' => array(
                'path' => 'features@user-admin;language=de_DE,mul_ZZ&original=blah',
                'expected' => array(
                    0 => 'features@user-admin;language=de_DE,mul_ZZ&original=blah',
                    'NodePath' => 'features',
                    1 => 'features',
                    'WorkspaceName' => 'user-admin',
                    2 => 'user-admin',
                    'Dimensions' => 'language=de_DE,mul_ZZ&original=blah',
                    3 => 'language=de_DE,mul_ZZ&original=blah'
                )
            )
        );
    }

    /**
     * @test
     * @dataProvider dataSourceForContextPathPattern
     */
    public function contextPathPatternShouldWorkWithContexts($path, $expected)
    {
        $matches = array();
        preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $path, $matches);

        $this->assertSame($expected, $matches);
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
        $this->assertEquals(0, $result, 'The invalid context path yielded matches: ' . print_r($matches, true));
    }

    /**
     * @test
     */
    public function createNodeWithAutoCreatedChildNodesAndNoIdentifierUsesGeneratedIdentifierOfNodeForChildNodes()
    {
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $mockContext->expects($this->any())->method('getTargetDimensions')->will($this->returnValue(array('language' => 'mul_ZZ')));
        $mockFirstLevelNodeCache = $this->createMock(FirstLevelNodeCache::class);
        $mockContext->expects($this->any())->method('getFirstLevelNodeCache')->will($this->returnValue($mockFirstLevelNodeCache));

        $mockNodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockSubNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();

        $mockNodeType->expects($this->any())->method('getDefaultValuesForProperties')->will($this->returnValue(array()));
        $mockNodeType->expects($this->any())->method('getAutoCreatedChildNodes')->will($this->returnValue(array(
            'subnode1' => $mockSubNodeType
        )));

        $i = 0;
        $generatedIdentifiers = array();
        $node = $this->getMockBuilder(Node::class)->setMethods(array('createSingleNode'))->setConstructorArgs(array($mockNodeData, $mockContext))->getMock();
        $node->expects($this->any())->method('createSingleNode')->will($this->returnCallback(function () use (&$i, &$generatedIdentifiers, $mockSubNodeType) {
            $newNode = $this->createMock(NodeInterface::class);
            $newNode->expects($this->any())->method('getIdentifier')->will($this->returnValue('node-' . $i++));

            $newNode->expects($this->once())->method('createNode')->with('subnode1', $mockSubNodeType, $this->callback(function ($identifier) use (&$generatedIdentifiers, $i) {
                $generatedIdentifiers[$i] = $identifier;
                return true;
            }));

            return $newNode;
        }));

        $node->createNode('foo', $mockNodeType);
        $node->createNode('bar', $mockNodeType);

        $this->assertNotSame($generatedIdentifiers[1], $generatedIdentifiers[2], 'Child nodes should have distinct identifiers');
    }
}
