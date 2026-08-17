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
use PHPUnit\Framework\Attributes\Test;
use Neos\ContentRepository\Domain\Model\NodeData;
use PHPUnit\Framework\Attributes\DataProvider;
use Neos\ContentRepository\Domain\Model\ArrayPropertyCollection;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeService;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Utility\Algorithms;

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
            ->willReturn($this->subNodeTypesFixture);
        $mockNodeTypeManager->expects(self::any())
            ->method('getNodeType')
            ->willReturnCallback(function ($nodeTypeName) {
                return new NodeType($nodeTypeName, [], []);
            });

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
            ->willReturn($nodeTypeName);
        $mockNodeType->expects(self::any())
            ->method('__toString')
            ->willReturn($nodeTypeName);

        return $mockNodeType;
    }

    #[Test]
    public function setDefaultValueOnlyIfTheCurrentPropertyIsNull()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->willReturn($mockNodeType);

        $mockNode->expects(self::once())
            ->method('getProperty')
            ->with('title')
            ->willReturn(null);

        $mockNode->expects(self::once())
            ->method('setProperty')
            ->with('title', 'hello');

        $mockNodeType->expects(self::once())
            ->method('getDefaultValuesForProperties')
            ->willReturn([
                'title' => 'hello'
            ]);

        $nodeService->setDefaultValues($mockNode);
    }

    #[Test]
    public function setDefaultDateValueOnlyIfTheCurrentPropertyIsNull()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.Neos:Content');

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->willReturn($mockNodeType);

        $mockNode->expects(self::once())
            ->method('getProperty')
            ->with('date')
            ->willReturn(null);

        $mockNode->expects(self::once())
            ->method('setProperty')
            ->with('date', new \DateTime('2014-09-03'));

        $mockNodeType->expects(self::once())
            ->method('getDefaultValuesForProperties')
            ->willReturn([
                'date' => new \DateTime('2014-09-03')
            ]);

        $nodeService->setDefaultValues($mockNode);
    }

    #[Test]
    public function setDefaultValueNeverReplaceExistingValue()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->willReturn($mockNodeType);

        $mockNode->expects(self::once())
            ->method('getProperty')
            ->with('title')
            ->willReturn('Existing value');

        $mockNode->expects(self::never())
            ->method('setProperty');

        $mockNodeType->expects(self::once())
            ->method('getDefaultValuesForProperties')
            ->willReturn([
                'title' => 'hello'
            ]);

        $nodeService->setDefaultValues($mockNode);
    }

    #[Test]
    public function createChildNodesTryToCreateAllConfiguredChildNodes()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->onlyMethods(['getNodeType', 'createNode', 'getIdentifier'])->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');
        $firstChildNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');
        $secondChildNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');

        $mockNodeType->expects(self::once())
            ->method('getAutoCreatedChildNodes')
            ->willReturn([
                'first-child-node-name' => $firstChildNodeType,
                'second-child-node-name' => $secondChildNodeType
            ]);

        $mockNode->method('getIdentifier')->willReturn(Algorithms::generateUUID());

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->willReturn($mockNodeType);
        $matcher = self::atLeast(2);

        $mockNode->expects($matcher)
            ->method('createNode')->willReturnCallback(function (...$parameters) use ($matcher, $firstChildNodeType, $secondChildNodeType) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('first-child-node-name', $parameters[0]);
                $this->assertSame($firstChildNodeType, $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('second-child-node-name', $parameters[0]);
                $this->assertSame($secondChildNodeType, $parameters[1]);
            }
            return $this->createMock(NodeInterface::class);
        });

        $nodeService->createChildNodes($mockNode);
    }

    #[Test]
    public function cleanUpPropertiesRemoveAllUndeclaredProperties()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $mockNodeData = $this->getMockBuilder(NodeData::class)->disableOriginalConstructor()->getMock();
        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Content');

        $mockNodeData->expects(self::once())
            ->method('removeProperty')
            ->with('invalidProperty');

        $mockNodeType->expects(self::once())
            ->method('getProperties')
            ->willReturn([
                'title' => [],
                'description' => []
            ]);

        $mockNode->expects(self::once())
            ->method('isRemoved')
            ->willReturn(false);

        $mockNode->expects(self::once())
            ->method('getNodeData')
            ->willReturn($mockNodeData);

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->willReturn($mockNodeType);

        $mockNode->expects(self::once())
            ->method('getProperties')
            ->willReturn(new ArrayPropertyCollection([
                'title' => 'hello',
                'description' => 'world',
                'invalidProperty' => 'world'
            ]));

        $nodeService->cleanUpProperties($mockNode);
    }

    /**
     * TODO: Adjust after the removal of child nodes is implemented again.
     */
    #[Test]
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
            ->willReturn($mockContentNodeType);
        $mockFirstChildNode->expects(self::any())
            ->method('getName')
            ->willReturn('main');
        $mockFirstChildNode->expects(self::never())
            ->method('remove');

        $mockSecondChildNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $mockSecondChildNode->expects(self::any())
            ->method('getNodeType')
            ->willReturn($mockContentNodeType);
        $mockSecondChildNode->expects(self::any())
            ->method('getName')
            ->willReturn('sidebar');
        $mockSecondChildNode->expects(self::never())
            ->method('remove');

        $mockThirdChildNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $mockThirdChildNode->expects(self::any())
            ->method('getNodeType')
            ->willReturn($mockContentNodeType);
        $mockThirdChildNode->expects(self::any())
            ->method('getName')
            ->willReturn('footer');
        $mockThirdChildNode->expects(self::once())
            ->method('remove');

        $mockMainChildNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:ContentCollection');
        $mockSidebarChildNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:ContentCollection');
        $mockNodeType->expects(self::once())
            ->method('getAutoCreatedChildNodes')
            ->willReturn([
                'main' => $mockMainChildNodeType,
                'sidebar' => $mockSidebarChildNodeType
            ]);

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->willReturn($mockNodeType);

        $mockNode->expects(self::once())
            ->method('getChildNodes')
            ->willReturn([
                $mockFirstChildNode,
                $mockSecondChildNode,
                $mockThirdChildNode
            ]);

        $nodeService->cleanUpChildNodes($mockNode);
    }

    /**
     * TODO: Adjust after the removal of child nodes is implemented again.
     */
    #[Test]
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
            ->willReturn($mockContentNodeType);
        $mockFirstChildNode->expects(self::any())
            ->method('getName')
            ->willReturn('sidebar');
        $mockFirstChildNode->expects(self::never())
            ->method('remove');

        $mockMainChildNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:ContentCollection');
        $mockNodeType->expects(self::once())
            ->method('getAutoCreatedChildNodes')
            ->willReturn([
                'main' => $mockMainChildNodeType
            ]);

        $mockNode->expects(self::once())
            ->method('getNodeType')
            ->willReturn($mockNodeType);

        $mockNode->expects(self::once())
            ->method('getChildNodes')
            ->willReturn([
                $mockFirstChildNode,
            ]);

        $nodeService->cleanUpChildNodes($mockNode);
    }

    #[Test]
    public function isNodeOfTypeReturnTrueIsTheGivenNodeIsSubNodeOfTheGivenType()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:MyFinalType');

        $mockNode->expects(self::atLeastOnce())
            ->method('getNodeType')
            ->willReturn($mockNodeType);

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:ContentObject');
        self::assertTrue($nodeService->isNodeOfType($mockNode, $mockNodeType));
    }

    #[Test]
    public function isNodeOfTypeReturnTrueIsTheGivenNodeHasTheSameTypeOfTheGivenType()
    {
        $nodeService = $this->createNodeService();

        $mockNode = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();

        $mockNodeType = $this->mockNodeType('Neos.ContentRepository.Testing:Document');

        $mockNode->expects(self::atLeastOnce())
            ->method('getNodeType')
            ->willReturn($mockNodeType);

        self::assertTrue($nodeService->isNodeOfType($mockNode, $mockNodeType));
    }


    /**
     * @return array
     */
    public static function abnormalPaths()
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
     */
    #[DataProvider('abnormalPaths')]
    #[Test]
    public function normalizePathReturnsANormalizedAbsolutePath($currentPath, $relativePath, $normalizedPath)
    {
        $nodeService = $this->createNodeService();
        self::assertSame($normalizedPath, $nodeService->normalizePath($relativePath, $currentPath));
    }

    #[Test]
    public function normalizePathThrowsInvalidArgumentExceptionOnPathContainingDoubleSlash()
    {
        $this->expectException(\InvalidArgumentException::class);
        $nodeService = $this->createNodeService();
        $nodeService->normalizePath('foo//bar', '/');
    }
}
