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
 * Tests for the NodeType implementation of TYPO3CR
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NodeTypeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Tamas Ilsinszki <ilsinszkitamas@yahoo.com>
	 */
	public function isOfNodeType() {
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate->setName('nodetype');
		$nodeTypeTemplate->setDeclaredSuperTypeNames(array('firstSupertype', 'secondSupertype'));
		$nodeType = new \F3\TYPO3CR\NodeType\NodeType($nodeTypeTemplate);

		$this->assertTrue($nodeType->isNodeType('nodetype'), 'Name of nodetype not nodetype.');
		$this->assertTrue($nodeType->isNodeType('firstSupertype'), 'Nodetype not subtype of firstSupertype.');
		$this->assertTrue($nodeType->isNodeType('secondSupertype'), 'Nodetype not subtype of secondSupertype.');
		$this->assertFalse($nodeType->isNodeType('thirdSupertype'), 'Nodetype not subtype of thirdSupertype.');
	}

}

?>