<?php
namespace TYPO3\Neos\Tests\Functional\Domain;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Neos\Tests\Functional\AbstractNodeTest;

/**
 * Tests checking correct Uri behavior for Neos nodes.
 */
class NodeUriTest extends AbstractNodeTest {

	/**
	 * @var string the Nodes fixture
	 */
	protected $fixtureFileName = 'Domain/Fixtures/NodeUriTestStructure.xml';

	/**
	 * @var string the context path of the node to load initially
	 */
	protected $nodeContextPath = '/sites/example/home';

	/**
	 * Note: You cannot hide a node in a context that doesn't show invisible content and afterwards move it because moving breaks then.
	 * The context used in this test therefor needs to be able to show hidden nodes.
	 * TODO: Investigate this behavior, currently it executes without problems but the result is wrong.
	 *
	 * @test
	 */
	public function hiddenNodeGetsNewUriSegmentOnMoveIfUriAlreadyExists() {
		$contextProperties = array_merge($this->node->getContext()->getProperties(), array('invisibleContentShown' => TRUE));
		$context = $this->contextFactory->create($contextProperties);
		$homeNode = $context->getNode($this->nodeContextPath);

		$historyNode = $homeNode->getNode('about-us/history');
		// History node will be moved inside products and gets an uriPathSegment that exists there already.
		$historyNode->setProperty('uriPathSegment', 'neos');
		$historyNode->setHidden(TRUE);

		$this->persistenceManager->persistAll();

		$historyNode->moveInto($homeNode->getNode('products'));

		$uriPathSegment = $historyNode->getProperty('uriPathSegment');
		$this->assertEquals('neos-1', $uriPathSegment);
	}

	/**
	 * @test
	 */
	public function nodeInNonDefaultDimensionGetsNewUriSegmentOnMoveIfUriAlreadyExists() {
		$homeNodeInNonDefaultDimension = $this->getNodeWithContextPath($this->nodeContextPath . '@live;language=de');

		$historyNode = $homeNodeInNonDefaultDimension->getNode('about-us/history');
		// History node will be moved inside products and gets an uriPathSegment that exists there already.
		$historyNode->setProperty('uriPathSegment', 'neos');

		$this->persistenceManager->persistAll();

		$historyNode->moveInto($homeNodeInNonDefaultDimension->getNode('products'));

		$uriPathSegment = $historyNode->getProperty('uriPathSegment');
		$this->assertEquals('neos-1', $uriPathSegment);
	}

}