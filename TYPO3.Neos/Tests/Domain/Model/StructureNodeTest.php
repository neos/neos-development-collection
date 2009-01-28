<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 */

/**
 * Testcase for the "Structure Node" domain model
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StructureNodeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aUniqueIDIsCreatedAutomaticallyWhileConstructingTheStructureNode() {
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();

		$this->assertEquals(36, strlen($node1->getId()));
		$this->assertEquals(36, strlen($node2->getId()));
		$this->assertNotEquals($node1->getId(), $node2->getId());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function childNodesCanBeAddedToAndRetrievedFromTheStructureNode() {
		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node3 = new \F3\TYPO3\Domain\Model\StructureNode();

		$rootNode->addChildNode($node1);
		$rootNode->addChildNode($node3);
		$rootNode->addChildNode($node2);

		$this->assertSame(array($node1, $node3, $node2), $rootNode->getChildNodes());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasChildNodesTellsIfTheStructureNodeHasChildNodes() {
		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();

		$this->assertFalse($rootNode->hasChildNodes());

		$rootNode->addChildNode($node1);
		$rootNode->addChildNode($node2);

		$this->assertTrue($rootNode->hasChildNodes());
	}

	/**
	 * @test
	 * @author
	 */
	public function oneContentObjectCanBeAttachedToAStructureNode() {
		$structureNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$content = new \F3\TYPO3\Domain\Model\Content\Text();

		$structureNode->setContent($content);
		$this->assertSame($content, $structureNode->getContent());
	}
}

?>