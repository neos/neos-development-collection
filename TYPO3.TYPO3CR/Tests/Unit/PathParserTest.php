<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Tests for the PathParser implementation of TYPO3CR
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PathParserTest extends \F3\Testing\BaseTestCase {

	/**
	 * Checks if we receive the root node properly
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function weGetTheRootNode() {
		$rootNode = $this->getMock('F3\PHPCR\NodeInterface');

		$node = \F3\TYPO3CR\PathParser::parsePath('/', $rootNode);
		$this->assertSame($rootNode, $node);

		$node = \F3\TYPO3CR\PathParser::parsePath('/./', $rootNode);
		$this->assertSame($rootNode, $node);
	}

	/**
	 * Checks if we receive a sub node property properly
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function propertiesAreRetrievedCorrectly() {
		$subNode = $this->getMock('F3\PHPCR\NodeInterface');
		$subNode->expects($this->once())->method('getName')->will($this->returnValue('News'));
		$subNode->expects($this->once())->method('hasProperty')->with('title')->will($this->returnValue(TRUE));
		$subNode->expects($this->once())->method('getProperty')->with('title')->will($this->returnValue('fake-title-property'));
		$subNode->expects($this->once())->method('getNodes')->will($this->returnValue(array()));
		$nodeIterator = array($subNode);
		$rootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$rootNode->expects($this->once())->method('getNodes')->will($this->returnValue($nodeIterator));
		$newsItem = \F3\TYPO3CR\PathParser::parsePath('News/title', $rootNode, \F3\TYPO3CR\PathParser::SEARCH_MODE_PROPERTIES);
		$this->assertEquals('fake-title-property', $newsItem);
	}

	/**
	 * Checks if we receive a sub node properly
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function subnodesAreRetrievedProperly() {
		$rootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$contentNode = $this->getMock('F3\PHPCR\NodeInterface');
		$homeNode = $this->getMock('F3\PHPCR\NodeInterface');

		$expectedHomeNodeIdentifier = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$homeNode->expects($this->any())->method('getDepth')->will($this->returnValue(2));
		$homeNode->expects($this->any())->method('getParent')->will($this->returnValue($contentNode));
		$homeNode->expects($this->any())->method('getName')->will($this->returnValue('Home'));
		$homeNode->expects($this->any())->method('getIdentifier')->will($this->returnValue($expectedHomeNodeIdentifier));

		$expectedContentNodeIdentifier = '96bca35d-1ef5-4a47-8b0c-0ddd69507d10';
		$contentNode->expects($this->any())->method('getDepth')->will($this->returnValue(1));
		$contentNode->expects($this->any())->method('getParent')->will($this->returnValue($rootNode));
		$contentNode->expects($this->any())->method('getName')->will($this->returnValue('Content'));
		$contentNode->expects($this->any())->method('getIdentifier')->will($this->returnValue($expectedContentNodeIdentifier));
		$contentNode->expects($this->any())->method('getNodes')->will($this->returnValue(array($homeNode)));

		$rootNode->expects($this->any())->method('getNodes')->will($this->returnValue(array($contentNode)));

		$node = \F3\TYPO3CR\PathParser::parsePath('/Content', $rootNode);
		$this->assertEquals($expectedContentNodeIdentifier, $node->getIdentifier(), 'The path parser did not return the correct content node.');

		$node = \F3\TYPO3CR\PathParser::parsePath('/Content/', $rootNode);
		$this->assertEquals($expectedContentNodeIdentifier, $node->getIdentifier(), 'The path parser did not return the correct content node.');

		$node = \F3\TYPO3CR\PathParser::parsePath('/Content/.', $rootNode);
		$this->assertEquals($expectedContentNodeIdentifier, $node->getIdentifier(), 'The path parser did not return the correct content node.');

		$node = \F3\TYPO3CR\PathParser::parsePath('Content/..', $rootNode);
		$this->assertEquals($rootNode->getIdentifier(), $node->getIdentifier(), 'The path parser did not return the correct root node.');

		$node = \F3\TYPO3CR\PathParser::parsePath('Content/./Home', $rootNode);
		$this->assertEquals($expectedHomeNodeIdentifier, $node->getIdentifier(), 'The path parser did not return the home page.');
	}

}
?>