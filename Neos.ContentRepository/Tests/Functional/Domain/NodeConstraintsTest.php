<?php
namespace Neos\ContentRepository\Tests\Functional\Domain;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;

/**
 * Functional test case for node constraints
 */
class NodeConstraintsTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var NodeTypeManager
     *
     */
    protected $nodeTypeManager;

    /**
     * @var NodeInterface
     */
    protected $rootNode;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->nodeDataRepository = new NodeDataRepository();
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);

        $workspace = new Workspace('live');
        $this->objectManager->get(WorkspaceRepository::class)->add($workspace);
        $this->persistenceManager->persistAll();

        $context = $this->contextFactory->create(['workspaceName' => 'live']);
        $this->rootNode = $context->getRootNode();
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', []);
    }

    /**
     * @test
     */
    public function movingNodeToWhereItsTypeIsDisallowedThrowsException()
    {
        $this->expectException(NodeConstraintException::class);
        $documentNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Document');
        $contentNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Content');
        $documentNode = $this->rootNode->createNode('document', $documentNodeType);
        $contentNode = $this->rootNode->createNode('content', $contentNodeType);
        $documentNode->moveInto($contentNode);
    }

    /**
     * @test
     */
    public function movingNodeToWhereItsSuperTypeIsDisallowedThrowsException()
    {
        $this->expectException(NodeConstraintException::class);
        $nodeTypeExtendingDocument = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Page');
        $nodeTypeExtendingContent = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Text');
        $documentNode = $this->rootNode->createNode('document', $nodeTypeExtendingDocument);
        $contentNode = $this->rootNode->createNode('content', $nodeTypeExtendingContent);
        $documentNode->moveInto($contentNode);
    }

    /**
     * @test
     */
    public function creatingNodeInChildNodeWithChildNodeConstraintsThrowsException()
    {
        $this->expectException(NodeConstraintException::class);
        $nodeTypeWithChildNodeAndConstraints = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithSubnodesAndConstraints');
        $documentNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Document');
        $nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $nodeTypeWithChildNodeAndConstraints);
        $childNode = $nodeWithChildNode->getNode('subnode1');
        $childNode->createNode('document', $documentNodeType);
    }

    /**
     * @test
     */
    public function childNodeWithChildNodeConstraintsAndNodeTypeConstraintsWorks()
    {
        $nodeTypeWithChildNodeAndConstraints = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithSubnodesAndConstraints');
        $headlineNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Headline');

        $nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $nodeTypeWithChildNodeAndConstraints);
        $childNode = $nodeWithChildNode->getNode('subnode1');
        $childNode->createNode('headline', $headlineNodeType);
        self::assertCount(1, $childNode->getChildNodes());
    }

    /**
     * @test
     */
    public function childNodeWithChildNodeConstraintsAndNodeTypeConstraintsThrowsException()
    {
        $this->expectException(NodeConstraintException::class);
        $nodeTypeWithChildNodeAndConstraints = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithSubnodesAndConstraints');
        $textNodeType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Text');

        $nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $nodeTypeWithChildNodeAndConstraints);
        $childNode = $nodeWithChildNode->getNode('subnode1');
        $childNode->createNode('text', $textNodeType);
    }

    /**
     * @test
     */
    public function inheritanceBasedConstraintsWork()
    {
        $testingNodeTypeWithSubnodes = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithSubnodes');
        $testingNodeTypeThatInheritsFromDocumentType = $this->nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:Page');

        $nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $testingNodeTypeWithSubnodes);
        $nodeWithChildNode->createNode('page', $testingNodeTypeThatInheritsFromDocumentType);
        self::assertCount(2, $nodeWithChildNode->getChildNodes());
    }
}
