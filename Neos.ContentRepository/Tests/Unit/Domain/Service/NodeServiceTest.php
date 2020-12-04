<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\ArrayPropertyCollection;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeService;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Testcase for the NodeService
 *
 */
class NodeServiceTest extends UnitTestCase
{
    /**
     * example node types
     *
     * @var array
     */
    protected $subNodeTypesFixture = [
        'Neos.ContentRepository.Testing:MyFinalType' => [
            'superTypes' => ['Neos.ContentRepository.Testing:ContentObject' => true],
            'final' => true
        ],
        'Neos.ContentRepository.Testing:Text' => [
            'superTypes' => ['Neos.ContentRepository.Testing:ContentObject' => true],
            'ui' => [
                'label' => 'Text',
            ],
            'properties' => [
                'headline' => [
                    'type' => 'string',
                    'placeholder' => 'Enter headline here'
                ],
                'text' => [
                    'type' => 'string',
                    'placeholder' => '<p>Enter text here</p>'
                ]
            ],
            'inlineEditableProperties' => ['headline', 'text']
        ]
    ];

    /**
     * @return NodeService
     */
    protected function createNodeService()
    {
        $nodeService = new NodeService();
        $mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)->disableOriginalConstructor()->getMock();
        $mockNodeTypeManager->expects(self::any())
            ->method('getSubNodeTypes')
            ->will(self::returnValue($this->subNodeTypesFixture));
        $mockNodeTypeManager->expects(self::any())
            ->method('getNodeType')
            ->will(self::returnCallback(function ($nodeTypeName) {
                return new NodeType($nodeTypeName, [], []);
            }));

        $this->inject($nodeService, 'nodeTypeManager', $mockNodeTypeManager);

        return $nodeService;
    }

    /**
     * @param string $nodeTypeName
     * @return mixed
     */
    protected function mockNodeType($nodeTypeName)
    {
        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->expects(self::any())
            ->method('getName')
            ->will(self::returnValue($nodeTypeName));
        $mockNodeType->expects(self::any())
            ->method('__toString')
            ->will(self::returnValue($nodeTypeName));

        return $mockNodeType;
    }

    /**
     * @test
     */
    public function setDefaultValueOnlyIfTheCurrentPropertyIsNull()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::once())
            ->method('getProperty')
            ->with('title')
            ->will(self::returnValue(null));

        $mockNode->expects(self::once())
            ->method('setProperty')
            ->with('title', 'hello');

        $mockNodeType->expects(self::once())
            ->method('getDefaultValuesForProperties')
            ->will(self::returnValue([
                'title' => 'hello'
            ]));

        $nodeService->setDefaultValues($mockNode);
    }

    /**
     * @test
     */
    public function setDefaultDateValueOnlyIfTheCurrentPropertyIsNull()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.Neos:Content');

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::once())
            ->method('getProperty')
            ->with('date')
            ->will(self::returnValue(null));

        $mockNode->expects(self::once())
            ->method('setProperty')
            ->with('date', new \DateTime('2014-09-03'));

        $mockNodeType->expects(self::once())
            ->method('getDefaultValuesForProperties')
            ->will(self::returnValue([
                'date' => new \DateTime('2014-09-03')
            ]));

        $nodeService->setDefaultValues($mockNode);
    }

    /**
     * @test
     */
    public function setDefaultValueNeverReplaceExistingValue()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::once())
            ->method('getProperty')
            ->with('title')
            ->will(self::returnValue('Existing value'));

        $mockNode->expects(self::never())
            ->method('setProperty');

        $mockNodeType->expects(self::once())
            ->method('getDefaultValuesForProperties')
            ->will(self::returnValue([
                'title' => 'hello'
            ]));

        $nodeService->setDefaultValues($mockNode);
    }

    /**
     * @test
     */
    public function createChildNodesTryToCreateAllConfiguredChildNodes()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');
        $firstChildNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');
        $secondChildNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');

        $mockNodeType->expects(self::once())
            ->method('getAutoCreatedChildNodes')
            ->will(self::returnValue([
                'first-child-node-name' => $firstChildNodeType,
                'second-child-node-name' => $secondChildNodeType
            ]));

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::atLeast(2))
            ->method('createNode')
            ->withConsecutive(['first-child-node-name', $firstChildNodeType], ['second-child-node-name', $secondChildNodeType]);

        $nodeService->createChildNodes($mockNode);
    }

    /**
     * @test
     */
    public function cleanUpPropertiesRemoveAllUndeclaredProperties()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $mockNodeData = $this->getMockBuilder(\Neos\ContentRepository\Domain\Model\NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');

        $mockNodeData->expects(self::once())
            ->method('removeProperty')
            ->with('invalidProperty');

        $mockNodeType->expects(self::once())
            ->method('getProperties')
            ->will(self::returnValue([
                'title' => [],
                'description' => []
            ]));

        $mockNode->expects(self::once())
            ->method('isRemoved')
            ->will(self::returnValue(false));

        $mockNode->expects(self::once())
            ->method('getNodeData')
            ->will(self::returnValue($mockNodeData));

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::once())
            ->method('getProperties')
            ->will(self::returnValue(new ArrayPropertyCollection([
                'title' => 'hello',
                'description' => 'world',
                'invalidProperty' => 'world'
            ])));

        $nodeService->cleanUpProperties($mockNode);
    }

    /**
     * @test
     *
     * TODO: Adjust after the removal of child nodes is implemented again.
     */
    public function cleanUpChildNodesRemoveAllUndeclaredChildNodes()
    {
        $this->markTestSkipped('Currently this functionality is disabled. We will introduce it again at a later point and then reenable this test.');

        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');

        $mockContentNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:ContentCollection');

        $mockFirstChildNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $mockFirstChildNode->expects(self::any())
            ->method('getNodeType')
            ->will(self::returnValue($mockContentNodeType));
        $mockFirstChildNode->expects(self::any())
            ->method('getName')
            ->will(self::returnValue('main'));
        $mockFirstChildNode->expects(self::never())
            ->method('remove');

        $mockSecondChildNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $mockSecondChildNode->expects(self::any())
            ->method('getNodeType')
            ->will(self::returnValue($mockContentNodeType));
        $mockSecondChildNode->expects(self::any())
            ->method('getName')
            ->will(self::returnValue('sidebar'));
        $mockSecondChildNode->expects(self::never())
            ->method('remove');

        $mockThirdChildNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $mockThirdChildNode->expects(self::any())
            ->method('getNodeType')
            ->will(self::returnValue($mockContentNodeType));
        $mockThirdChildNode->expects(self::any())
            ->method('getName')
            ->will(self::returnValue('footer'));
        $mockThirdChildNode->expects(self::once())
            ->method('remove');

        $mockMainChildNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:ContentCollection');
        $mockSidebarChildNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:ContentCollection');
        $mockNodeType->expects(self::once())
            ->method('getAutoCreatedChildNodes')
            ->will(self::returnValue([
                'main' => $mockMainChildNodeType,
                'sidebar' => $mockSidebarChildNodeType
            ]));

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::once())
            ->method('getChildNodes')
            ->will(self::returnValue([
                $mockFirstChildNode,
                $mockSecondChildNode,
                $mockThirdChildNode
            ]));

        $nodeService->cleanUpChildNodes($mockNode);
    }

    /**
     * @test
     *
     * TODO: Adjust after the removal of child nodes is implemented again.
     */
    public function cleanUpChildNodesNeverRemoveDocumentNode()
    {
        $this->markTestSkipped('Currently this functionality is disabled. We will introduce it again at a later point and then reenable this test.');

        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Page');

        $mockContentNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Document');

        $mockFirstChildNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $mockFirstChildNode->expects(self::any())
            ->method('getNodeType')
            ->will(self::returnValue($mockContentNodeType));
        $mockFirstChildNode->expects(self::any())
            ->method('getName')
            ->will(self::returnValue('sidebar'));
        $mockFirstChildNode->expects(self::never())
            ->method('remove');

        $mockMainChildNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:ContentCollection');
        $mockNodeType->expects(self::once())
            ->method('getAutoCreatedChildNodes')
            ->will(self::returnValue([
                'main' => $mockMainChildNodeType
            ]));

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->will(self::returnValue($mockNodeType));

        $mockNode->expects(self::once())
            ->method('getChildNodes')
            ->will(self::returnValue([
                $mockFirstChildNode,
            ]));

        $nodeService->cleanUpChildNodes($mockNode);
    }

    /**
     * @test
     */
    public function isNodeOfTypeReturnTrueIsTheGivenNodeIsSubNodeOfTheGivenType()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:MyFinalType');

        $mockNode->expects(self::atLeastOnce())
            ->method('getNodeType')
            ->will(self::returnValue($mockNodeType));

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:ContentObject');
        self::assertTrue($nodeService->isNodeOfType($mockNode, $mockNodeType));
    }

    /**
     * @test
     */
    public function isNodeOfTypeReturnTrueIsTheGivenNodeHasTheSameTypeOfTheGivenType()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Document');

        $mockNode->expects(self::atLeastOnce())
            ->method('getNodeType')
            ->will(self::returnValue($mockNodeType));

        self::assertTrue($nodeService->isNodeOfType($mockNode, $mockNodeType));
    }


    /**
     * @return array
     */
    public function abnormalPaths()
    {
        return [
            ['/', '/', '/'],
            ['/', '/.', '/'],
            ['/', '.', '/'],
            ['/', 'foo/bar', '/foo/bar'],
            ['/foo', '.', '/foo'],
            ['/foo', '/foo/.', '/foo'],
            ['/foo', '../', '/'],
            ['/foo/bar', '../baz', '/foo/baz'],
            ['/foo/bar', '../baz/../bar', '/foo/bar'],
            ['/foo/bar', '.././..', '/'],
            ['/foo/bar', '../../.', '/'],
            ['/foo/bar/baz', '../..', '/foo'],
            ['/foo/bar/baz', '../quux', '/foo/bar/quux'],
            ['/foo/bar/baz', '../quux/.', '/foo/bar/quux']
        ];
    }

    /**
     * @param string $currentPath
     * @param string $relativePath
     * @param string $normalizedPath
     * @test
     * @dataProvider abnormalPaths
     */
    public function normalizePathReturnsANormalizedAbsolutePath($currentPath, $relativePath, $normalizedPath)
    {
        $nodeService = $this->createNodeService();
        self::assertSame($normalizedPath, $nodeService->normalizePath($relativePath, $currentPath));
    }

    /**
     * @test
     */
    public function normalizePathThrowsInvalidArgumentExceptionOnPathContainingDoubleSlash()
    {
        $this->expectException(\InvalidArgumentException::class);
        $nodeService = $this->createNodeService();
        $nodeService->normalizePath('foo//bar', '/');
    }
}
