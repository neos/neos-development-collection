<?php
declare(ENCODING = 'utf-8');

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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_NodeType_NodeTypeTemplateTest extends F3_Testing_BaseTestCase {

	/**
	 * Make sure the NodeTypeTemplate is protoype
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function nodeTypeTemplateIsPrototype() {
		$firstInstance = $this->componentManager->getComponent('F3_TYPO3CR_NodeType_NodeTypeTemplate');
		$secondInstance = $this->componentManager->getComponent('F3_TYPO3CR_NodeType_NodeTypeTemplate');
		$this->assertNotSame($firstInstance, $secondInstance, 'F3_TYPO3CR_NodeType_NodeTypeTemplate is not prototype.');
	}

	/**
	 * Checks if a new NodeTypeTemplate has the default values required by JSR-283
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function newNodeTypeTemplateHasExpectedDefaults() {
		$nodeTypeTemplate = new F3_TYPO3CR_NodeType_NodeTypeTemplate();
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