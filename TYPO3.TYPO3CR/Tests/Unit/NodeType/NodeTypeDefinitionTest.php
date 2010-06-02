<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\NodeType;

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
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id: NodeTypeDefinitionTest.php 1811 2009-06-30 12:04:49Z ilsinszki $
 */

/**
 * Tests for the NodeTypeDefinition implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id: NodeTypeDefinitionTest.php 1811 2009-06-30 12:04:49Z ilsinszki $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NodeTypeDefinitionTest extends \F3\Testing\BaseTestCase {

	/**
	 * Checks if a new NodeTypeDefinition has correct default values
	 *
	 * @author Tamas Ilsinszki <ilsinszkitamas@yahoo.com>
	 * @test
	 */
	public function newNodeTypeDefinitionHasExpectedDefaults() {
		$nodeTypeDefinition = new \F3\TYPO3CR\NodeType\NodeTypeDefinition('testNodeTypeDefinition');
		$this->assertEquals(array('nt:base'), $nodeTypeDefinition->getDeclaredSupertypeNames(), 'The declared supertype names for a new NodeTypeDefinition must contain only nt:base');
		$this->assertFalse($nodeTypeDefinition->isAbstract(), 'A new NodeTypeDefinition may not be abstract');
		$this->assertFalse($nodeTypeDefinition->isMixin(), 'A new NodeTypeDefinition must be primary');
		$this->assertFalse($nodeTypeDefinition->hasOrderableChildNodes(), 'A new NodeTypeDefinition may not have orderable child nodes');
		$this->assertNull($nodeTypeDefinition->getPrimaryItemName(), 'The name of the primary item must be NULL for a new NodeTypeDefinition');
		$this->assertNull($nodeTypeDefinition->getDeclaredPropertyDefinitions(), 'The declared property definitions of a new NodeTypeDefinition must be NULL');
		$this->assertNull($nodeTypeDefinition->getDeclaredChildNodeDefinitions(), 'The declared child node definitions of a new NodeTypeDefinition must be NULL');
	}

}
?>
