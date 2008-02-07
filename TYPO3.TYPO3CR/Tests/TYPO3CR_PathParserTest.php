<?php
declare(encoding = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

require_once('TYPO3CR_BaseTest.php');

/**
 * Tests for the PathParser implementation of TYPO3CR
 *
 * @package		TYPO3CR
 * @subpackage	Tests
 * @version 	$Id: TYPO3CR_WorkspaceTest.php 328 2007-09-04 13:44:34Z robert $
 * @author 		Sebastian Kurfuerst <sebastian@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TYPO3CR_PathParserTest extends TYPO3CR_BaseTest {

	/**
	 * @var T3_TYPO3CR_Node
	 */
	protected $rootNode;

	/**
	 * @var T3_TYPO3CR_PathParser
	 */
	protected $pathParser;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->rootNode = $this->session->getRootNode();
		$this->pathParser = new T3_TYPO3CR_PathParser();
	}

	/**
	 * Checks if we receive the root node properly
	 * @test
	 */
	public function weGetTheRootNode() {
		$firstNode = $this->pathParser->parsePath('/', $this->rootNode);
		$this->assertEquals($this->rootNode, $firstNode, 'The path parser did not return the root node.');
		
		$thirdNode = $this->pathParser->parsePath('/./', $this->rootNode);
		$this->assertEquals($this->rootNode, $thirdNode, 'The path parser did not return the root node.');
	}

	/**
	 * Checks if we receive a sub node properly
	 * @test
	 */
	public function subnodesAreRetrievedProperly() {
		$expectedContentNodeUUID = '96bca35d-1ef5-4a47-8b0c-0bfc69507d01';
		$expectedHomeNodeUUID = '96bca35d-9ef5-4a47-8b0c-0bfc69507d05';
		$contentNode = $this->pathParser->parsePath('/Content', $this->rootNode);
		$this->assertEquals($expectedContentNodeUUID, $contentNode->getUUID(), 'The path parser did not return the correct content node.');
		
		$contentNode = $this->pathParser->parsePath('/Content/', $this->rootNode);
		$this->assertEquals($expectedContentNodeUUID, $contentNode->getUUID(), 'The path parser did not return the correct content node.');
		
		$contentNode = $this->pathParser->parsePath('/Content/.', $this->rootNode);
		$this->assertEquals($expectedContentNodeUUID, $contentNode->getUUID(), 'The path parser did not return the correct content node.');
		
		$rootNode = $this->pathParser->parsePath('Content/..', $this->rootNode);
		$this->assertEquals($rootNode->getUUID(), $this->rootNode->getUUID(), 'The path parser did not return the correct content node.');
		
		$homeNode = $this->pathParser->parsePath('Content/./Categories/Pages/Home', $this->rootNode);
		$this->assertEquals($expectedHomeNodeUUID, $homeNode->getUUID(), 'The path parser did not return the home page.');
	}

	/**
	 * Checks if we receive a sub node property properly
	 * @test
	 */
	public function propertiesAreRetrievedCorrectly() {
		$expectedTitle = 'News about the TYPO3CR';
		$newsItem = $this->pathParser->parsePath('Content/Categories/Pages/Home/News/title', $this->rootNode, T3_TYPO3CR_PathParserInterface::SEARCH_MODE_PROPERTIES);
		
		$this->assertEquals($expectedTitle, $newsItem->getString(), 'The path parser did not return the home page.');
	}

	/**
	 * Checks if we receive the same sub node property twice
	 * @test
	 */
	public function propertyObjectsAreIdentical() {
		$property1 = $this->pathParser->parsePath('Content/Categories/Pages/Home/News/title', $this->rootNode, T3_TYPO3CR_PathParserInterface::SEARCH_MODE_PROPERTIES);
		$property2 = $this->pathParser->parsePath('Content/Categories/Pages/Home/News/title', $this->rootNode, T3_TYPO3CR_PathParserInterface::SEARCH_MODE_PROPERTIES);
		$this->assertSame($property1, $property2, 'The path parser did not return the same object.');
	}

	/**
	 * Test for index based notation
	 * @test
	 */
	public function indexBasedNotationWorks() {
		throw new PHPUnit_Framework_IncompleteTestError('Test for same-name siblings not implemented yet.');
	}

	/**
	 * Test for fetching properties
	 * @test
	 */
	public function fetchingPropertiesWorks() {
		throw new PHPUnit_Framework_IncompleteTestError('Test for property fetching not implemented yet.');
	}

}
?>