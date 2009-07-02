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
 * @version $Id$
 */

/**
 * Tests for the NodeType implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NodeTypeTemplateTest extends \F3\Testing\BaseTestCase {

	/**
	 * Checks if a new NodeTypeTemplate has the default values required by JSR-283
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function newNodeTypeTemplateHasExpectedDefaults() {
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$this->assertNull($nodeTypeTemplate->getName(), 'The name of a new NodeTypeTemplate must be NULL');
		$this->assertEquals(array('nt:base'), $nodeTypeTemplate->getDeclaredSupertypeNames(), 'The declared supertype names for a new NodeTypeTemplate must contain only nt:base');
		$this->assertFalse($nodeTypeTemplate->isAbstract(), 'A new NodeTypeTemplate may not be abstract');
		$this->assertFalse($nodeTypeTemplate->isMixin(), 'A new NodeTypeTemplate must be primary');
		$this->assertFalse($nodeTypeTemplate->hasOrderableChildNodes(), 'A new NodeTypeTemplate may not have orderable child nodes');
		$this->assertNull($nodeTypeTemplate->getPrimaryItemName(), 'The name of the primary item must be NULL for a new NodeTypeTemplate');
		$this->assertNull($nodeTypeTemplate->getDeclaredPropertyDefinitions(), 'The declared property definitions of a new NodeTypeTemplate must be NULL');
		$this->assertNull($nodeTypeTemplate->getDeclaredChildNodeDefinitions(), 'The declared child node definitions of a new NodeTypeTemplate must be NULL');
	}
}
?>