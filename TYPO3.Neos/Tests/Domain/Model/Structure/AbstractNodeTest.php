<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Structure;

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
 * Testcase for the "Abstract Node" domain model
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AbstractNodeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function childNodesOrderIsUndefinedBeforeAddingTheFirstChildnode() {
		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'));
		$this->assertSame(\F3\TYPO3\Domain\Model\Structure\AbstractNode::CHILDNODESORDER_UNDEFINED, $rootNode->getChildNodesOrder());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addChildNodeAddsAChildNodeAfterExistingChildNodes() {
		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node2 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node3 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->addChildNode($node1);
		$rootNode->addChildNode($node3);
		$rootNode->addChildNode($node2);

		$expectedChildNodes = array('mul' => array('ZZ' => array($node1, $node3, $node2)));
		$this->assertSame($expectedChildNodes, $rootNode->getChildNodes());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addChildNodeSetsTheChildNodesOrderToOrdered() {
		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->addChildNode($node1);
		$this->assertSame(\F3\TYPO3\Domain\Model\Structure\AbstractNode::CHILDNODESORDER_ORDERED, $rootNode->getChildNodesOrder());
	}

	/**
	 * @test
	 * @expectedException F3\TYPO3\Domain\Exception\WrongNodeOrderMethod
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addChildNodeThrowsAnExceptionIfTheChildNodeOrderIsNotUndefinedOrOrdered() {
		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node2 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->setNamedChildNode('node1', $node1);
		$rootNode->addChildNode($node2);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addChildNodeAllowsForSpecifyingALocaleForTheChildNode() {
		$locale1 = new \F3\FLOW3\Locale\Locale('de-DE');
		$locale2 = new \F3\FLOW3\Locale\Locale('en-EN');

		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node2 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node3 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->addChildNode($node1);
		$rootNode->addChildNode($node3, $locale2);
		$rootNode->addChildNode($node2, $locale1);

		$actualChildNodes = $rootNode->getChildNodes();

		$this->assertSame($node1, current($actualChildNodes['mul']['ZZ']));
		$this->assertSame($node2, current($actualChildNodes['de']['DE']));
		$this->assertSame($node3, current($actualChildNodes['en']['EN']));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNamedChildNodeSetsAChildNodeByItsName() {
		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node2 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node3 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->setNamedChildNode('node1', $node1);
		$rootNode->setNamedChildNode('node3', $node3);
		$rootNode->setNamedChildNode('node2', $node2);

		$actualChildNodes = $rootNode->getChildNodes();

		$this->assertSame($node1, $actualChildNodes['mul']['ZZ']['node1']);
		$this->assertSame($node2, $actualChildNodes['mul']['ZZ']['node2']);
		$this->assertSame($node3, $actualChildNodes['mul']['ZZ']['node3']);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNamedChildNodeSetsTheChildNodesOrderToNamed() {
		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->setNamedChildNode('node1', $node1);
		$this->assertSame(\F3\TYPO3\Domain\Model\Structure\AbstractNode::CHILDNODESORDER_NAMED, $rootNode->getChildNodesOrder());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNamedChildNodeAllowsForSpecifyingALocaleForTheChildNode() {
		$locale1 = new \F3\FLOW3\Locale\Locale('de-DE');
		$locale2 = new \F3\FLOW3\Locale\Locale('en-EN');

		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node2 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node3 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->setNamedChildNode('node1', $node1);
		$rootNode->setNamedChildNode('node2', $node2, $locale1);
		$rootNode->setNamedChildNode('node3', $node3, $locale2);

		$actualChildNodes = $rootNode->getChildNodes();

		$this->assertSame($node1, $actualChildNodes['mul']['ZZ']['node1']);
		$this->assertSame($node2, $actualChildNodes['de']['DE']['node2']);
		$this->assertSame($node3, $actualChildNodes['en']['EN']['node3']);
	}

	/**
	 * @test
	 * @expectedException F3\TYPO3\Domain\Exception\WrongNodeOrderMethod
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNamedChildNodeThrowsAnExceptionIfTheChildNodeOrderIsNotUndefinedOrNamed() {
		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node2 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->addChildNode($node1);
		$rootNode->setNamedChildNode('node2', $node2);
	}

	/**
	 * @test
	 * @expectedException F3\TYPO3\Domain\Exception\NodeAlreadyExists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNamedChildNodeThrowsAnExceptionIfTheANodeWithThatNameAlreadyExists() {
		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node2 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->setNamedChildNode('node', $node1);
		$rootNode->setNamedChildNode('node', $node2);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodesOnlyReturnsNodesMatchingTheSpecifiedContext() {
		$locale = new \F3\FLOW3\Locale\Locale('en-EN');
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getLocale')->will($this->returnValue($locale));

		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node2 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->addChildNode($node1);
		$rootNode->addChildNode($node2, $locale);

		$actualChildNodes = $rootNode->getChildNodes($mockContentContext);
		$this->assertSame($node2, current($actualChildNodes));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodesReturnsAnEmptyArrayIfNoNodesMatchedTheLocale() {
		$locale1 = new \F3\FLOW3\Locale\Locale('en-EN');

		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node2 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$rootNode->addChildNode($node1);
		$rootNode->addChildNode($node2, $locale1);

		$locale2 = new \F3\FLOW3\Locale\Locale('dk-DK');
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array(), '', FALSE);
		$mockContentContext->expects($this->any())->method('getLocale')->will($this->returnValue($locale2));

		$this->assertSame(array(), $rootNode->getChildNodes($mockContentContext));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasChildNodesTellsIfTheNodeHasChildNodes() {
		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node1 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$node2 = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));

		$this->assertFalse($rootNode->hasChildNodes());

		$rootNode->addChildNode($node1);
		$this->assertTrue($rootNode->hasChildNodes());

		$rootNode->addChildNode($node2);

		$this->assertTrue($rootNode->hasChildNodes());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addConfigurationAttachesConfigurationToTheNode() {
		$mockConfiguration = $this->getMock('F3\TYPO3\Domain\Model\Configuration\ConfigurationInterface');

		$rootNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\AbstractNode', array('dummy'), array(), uniqid('Node'));
		$rootNode->addConfiguration($mockConfiguration);

		$actualConfigurations = $rootNode->getConfigurations();
		$this->assertSame(1, count($actualConfigurations));
		$this->assertTrue($actualConfigurations->contains($mockConfiguration));
	}
}

?>