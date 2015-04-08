<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Functional test case for node constraints
 */
class NodeConstraintsTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 *
	 */
	protected $nodeTypeManager;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $rootNode;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->nodeDataRepository = new \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository();
		$this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$this->nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$context = $this->contextFactory->create(array('workspaceName' => 'live'));
		$this->rootNode = $context->getRootNode();
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->inject($this->contextFactory, 'contextInstances', array());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeConstraintException
	 */
	public function movingNodeToWhereItsTypeIsDisallowedThrowsException() {
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
	public function movingNodeToWhereItsSuperTypeIsDisallowedThrowsException() {
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
	public function creatingNodeInChildNodeWithChildNodeConstraintsThrowsException() {
		$nodeTypeWithChildNodeAndConstraints = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodesAndConstraints');
		$documentNodeType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Document');
		$nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $nodeTypeWithChildNodeAndConstraints);
		$childNode = $nodeWithChildNode->getNode('subnode1');
		$childNode->createNode('document', $documentNodeType);
	}

	/**
	 * @test
	 */
	public function childNodeWithChildNodeConstraintsAndNodeTypeConstraintsWorks() {
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
	public function childNodeWithChildNodeConstraintsAndNodeTypeConstraintsThrowsException() {
		$nodeTypeWithChildNodeAndConstraints = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodesAndConstraints');
		$textNodeType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Text');

		$nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $nodeTypeWithChildNodeAndConstraints);
		$childNode = $nodeWithChildNode->getNode('subnode1');
		$childNode->createNode('text', $textNodeType);
	}

	/**
	 * @test
	 */
	public function inheritanceBasedConstraintsWork() {
		$testingNodeTypeWithSubnodes = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeTypeWithSubnodes');
		$testingNodeTypeThatInheritsFromDocumentType = $this->nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:Page');

		$nodeWithChildNode = $this->rootNode->createNode('node-with-child-node', $testingNodeTypeWithSubnodes);
		$nodeWithChildNode->createNode('page', $testingNodeTypeThatInheritsFromDocumentType);
		$this->assertCount(2, $nodeWithChildNode->getChildNodes());
	}
}