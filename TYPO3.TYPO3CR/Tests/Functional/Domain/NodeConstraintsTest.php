<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

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
    public function setUp()
    {
        parent::setUp();
        $this->nodeDataRepository = new NodeDataRepository();
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);

        $workspace = new Workspace('live');
        $this->objectManager->get(WorkspaceRepository::class)->add($workspace);
        $this->persistenceManager->persistAll();

        $context = $this->contextFactory->create(array('workspaceName' => 'live'));
        $this->rootNode = $context->getRootNode();
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', array());
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeConstraintException
     */
    public function movingNodeToWhereItsTypeIsDisallowedThrowsException()
    {
        $documentNodeType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Document');
        $contentNodeType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Content');
        $documentNode = $this->rootNode->createNode('document', $documentNodeType);
        $contentNode = $this->rootNode->createNode('content', $contentNodeType);
        $documentNode->moveInto($contentNode);
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeConstraintException
     */
    public function movingNodeToWhereItsSuperTypeIsDisallowedThrowsException()
    {
        $nodeTypeExtendingDocument = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Page');
        $nodeTypeExtendingContent = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Text');
        $documentNode = $this->rootNode->createNode('document', $nodeTypeExtendingDocument);
        $contentNode = $this->rootNode->createNode('content', $nodeTypeExtendingContent);
        $documentNode->moveInto($contentNode);
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeConstraintException
     */
    public function creatingNodeInChildNodeWithChildNodeConstraintsThrowsException()
    {
        $nodeTypeWithChildNodeAndConstraints = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodesAndConstraints');
        $documentNodeType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Document');
        $nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $nodeTypeWithChildNodeAndConstraints);
        $childNode = $nodeWithChildNode->getNode('subnode1');
        $childNode->createNode('document', $documentNodeType);
    }

    /**
     * @test
     */
    public function childNodeWithChildNodeConstraintsAndNodeTypeConstraintsWorks()
    {
        $nodeTypeWithChildNodeAndConstraints = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodesAndConstraints');
        $headlineNodeType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Headline');

        $nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $nodeTypeWithChildNodeAndConstraints);
        $childNode = $nodeWithChildNode->getNode('subnode1');
        $childNode->createNode('headline', $headlineNodeType);
        $this->assertCount(1, $childNode->getChildNodes());
    }

    /**
     * @test
     * @expectedException \TYPO3\TYPO3CR\Exception\NodeConstraintException
     */
    public function childNodeWithChildNodeConstraintsAndNodeTypeConstraintsThrowsException()
    {
        $nodeTypeWithChildNodeAndConstraints = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodesAndConstraints');
        $textNodeType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Text');

        $nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $nodeTypeWithChildNodeAndConstraints);
        $childNode = $nodeWithChildNode->getNode('subnode1');
        $childNode->createNode('text', $textNodeType);
    }

    /**
     * @test
     */
    public function inheritanceBasedConstraintsWork()
    {
        $testingNodeTypeWithSubnodes = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodes');
        $testingNodeTypeThatInheritsFromDocumentType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Page');

        $nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $testingNodeTypeWithSubnodes);
        $nodeWithChildNode->createNode('page', $testingNodeTypeThatInheritsFromDocumentType);
        $this->assertCount(2, $nodeWithChildNode->getChildNodes());
    }
}
