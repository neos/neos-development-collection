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
	public function childNodesOrderIsUndefinedBeforeAddingTheFirstChildnode() {
		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$this->assertSame(\F3\TYPO3\Domain\Model\StructureNode::CHILDNODESORDER_UNDEFINED, $rootNode->getChildNodesOrder());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addChildNodeAddsAChildNodeAfterExistingChildNodes() {
		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node3 = new \F3\TYPO3\Domain\Model\StructureNode();

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
		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();

		$rootNode->addChildNode($node1);
		$this->assertSame(\F3\TYPO3\Domain\Model\StructureNode::CHILDNODESORDER_ORDERED, $rootNode->getChildNodesOrder());
	}

	/**
	 * @test
	 * @expectedException F3\TYPO3\Domain\Exception\WrongNodeOrderMethod
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addChildNodeThrowsAnExceptionIfTheChildNodeOrderIsNotUndefinedOrOrdered() {
		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();

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

		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node3 = new \F3\TYPO3\Domain\Model\StructureNode();

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
		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node3 = new \F3\TYPO3\Domain\Model\StructureNode();

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
		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();

		$rootNode->setNamedChildNode('node1', $node1);
		$this->assertSame(\F3\TYPO3\Domain\Model\StructureNode::CHILDNODESORDER_NAMED, $rootNode->getChildNodesOrder());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNamedChildNodeAllowsForSpecifyingALocaleForTheChildNode() {
		$locale1 = new \F3\FLOW3\Locale\Locale('de-DE');
		$locale2 = new \F3\FLOW3\Locale\Locale('en-EN');

		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node3 = new \F3\TYPO3\Domain\Model\StructureNode();

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
		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();

		$rootNode->addChildNode($node1);
		$rootNode->setNamedChildNode('node2', $node2);
	}

	/**
	 * @test
	 * @expectedException F3\TYPO3\Domain\Exception\NodeAlreadyExists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNamedChildNodeThrowsAnExceptionIfTheANodeWithThatNameAlreadyExists() {
		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();

		$rootNode->setNamedChildNode('node', $node1);
		$rootNode->setNamedChildNode('node', $node2);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodesOnlyReturnsNodesMatchingTheSpecifiedLocale() {
		$locale = new \F3\FLOW3\Locale\Locale('en-EN');

		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();

		$rootNode->addChildNode($node1);
		$rootNode->addChildNode($node2, $locale);

		$actualChildNodes = $rootNode->getChildNodes($locale, FALSE);
		$this->assertSame($node2, current($actualChildNodes['en']['EN']));
		$this->assertFALSE(isset($actualChildNodes['mul']['ZZ']));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodesReturnsAnEmptyArrayIfNoNodesMatchedTheLocale() {
		$locale1 = new \F3\FLOW3\Locale\Locale('en-EN');

		$rootNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$node1 = new \F3\TYPO3\Domain\Model\StructureNode();
		$node2 = new \F3\TYPO3\Domain\Model\StructureNode();

		$rootNode->addChildNode($node1);
		$rootNode->addChildNode($node2, $locale1);

		$locale2 = new \F3\FLOW3\Locale\Locale('dk-DK');
		$this->assertSame(array(), $rootNode->getChildNodes($locale2, FALSE));
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
		$this->assertTrue($rootNode->hasChildNodes());

		$rootNode->addChildNode($node2);

		$this->assertTrue($rootNode->hasChildNodes());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentAttachesContentToTheStructureNode() {
		$locale = new \F3\FLOW3\Locale\Locale('en-EN');
		$mockContent = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent->expects($this->once())->method('getLocale')->will($this->returnValue($locale));

		$structureNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$structureNode->setContent($mockContent);

		$this->assertSame($mockContent, $structureNode->getContent($locale, FALSE));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentOverwritesAnyExistingContentMatchingTheSameLanguageAndRegion() {
		$locale1 = new \F3\FLOW3\Locale\Locale('en-EN');
		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent1->expects($this->once())->method('getLocale')->will($this->returnValue($locale1));

		$locale2 = clone $locale1;
		$mockContent2 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent2->expects($this->once())->method('getLocale')->will($this->returnValue($locale2));

		$structureNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$structureNode->setContent($mockContent1);
		$structureNode->setContent($mockContent2);

		$locale3 = clone $locale1;
		$this->assertSame($mockContent2, $structureNode->getContent($locale3, FALSE));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentSetsTheStructureNodesContentTypeAccordingly() {
		$locale = new \F3\FLOW3\Locale\Locale('en-EN');
		$mockContent = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent->expects($this->once())->method('getLocale')->will($this->returnValue($locale));

		$structureNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$structureNode->setContent($mockContent);

		$this->assertSame(get_class($mockContent), $structureNode->getContentType());
	}

	/**
	 * @test
	 * @expectedException \F3\TYPO3\Domain\Exception\InvalidContentType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentThrowsAnExceptionIfContentIsAddedNotMatchingTheTypeOfExistingContent() {
		$locale = new \F3\FLOW3\Locale\Locale('en-EN');

		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array(), uniqid('SomeContentClassName'));
		$mockContent1->expects($this->once())->method('getLocale')->will($this->returnValue($locale));

		$mockContent2 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array(), uniqid('SomeContentClassName'));

		$structureNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$structureNode->setContent($mockContent1);
		$structureNode->setContent($mockContent2);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentReturnsNullIfNoContentMatchedTheLocale() {
		$locale1 = new \F3\FLOW3\Locale\Locale('en-EN');
		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent1->expects($this->once())->method('getLocale')->will($this->returnValue($locale1));

		$locale2 = new \F3\FLOW3\Locale\Locale('de-DE');
		$mockContent2 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockContent2->expects($this->once())->method('getLocale')->will($this->returnValue($locale2));

		$structureNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$structureNode->setContent($mockContent1);
		$structureNode->setContent($mockContent2);

		$locale3 = new \F3\FLOW3\Locale\Locale('dk-DK');
		$this->assertNULL($structureNode->getContent($locale3, FALSE));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeContentDetachesTheGivenContentObjectFromTheStructureNode() {
		$locale1 = new \F3\FLOW3\Locale\Locale('en-EN');
		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array());
		$mockContent1->expects($this->any())->method('getLocale')->will($this->returnValue($locale1));

		$locale2 = new \F3\FLOW3\Locale\Locale('de-DE');
		$mockContent2 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array());
		$mockContent2->expects($this->any())->method('getLocale')->will($this->returnValue($locale2));

		$structureNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$structureNode->setContent($mockContent1);
		$structureNode->setContent($mockContent2);

		$structureNode->removeContent($mockContent2);

		$this->assertSame($mockContent1, $structureNode->getContent($locale1, FALSE));
		$this->assertNull($structureNode->getContent($locale2, FALSE));
	}

	/**
	 * @test
	 * @expectedException \F3\TYPO3\Domain\Exception\NoSuchContent
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeContentThrowsAnExceptionIfTheGivenContentIsNotAttachedToTheStructureNode() {
		$locale1 = new \F3\FLOW3\Locale\Locale('en-EN');
		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array());
		$mockContent1->expects($this->any())->method('getLocale')->will($this->returnValue($locale1));

		$locale2 = new \F3\FLOW3\Locale\Locale('de-DE');
		$mockContent2 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array());
		$mockContent2->expects($this->any())->method('getLocale')->will($this->returnValue($locale2));

		$structureNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$structureNode->setContent($mockContent1);

		$structureNode->removeContent($mockContent2);
	}

	/**
	 * @test
	 */
	public function removeContentUnsetsTheContentTypeIfTheLastContentObjectIsRemoved() {
		$locale1 = new \F3\FLOW3\Locale\Locale('en-EN');
		$mockContent1 = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface', array(), array());
		$mockContent1->expects($this->any())->method('getLocale')->will($this->returnValue($locale1));

		$structureNode = new \F3\TYPO3\Domain\Model\StructureNode();
		$structureNode->setContent($mockContent1);

		$structureNode->removeContent($mockContent1);

		$this->assertNull($structureNode->getContentType());

	}
}

?>