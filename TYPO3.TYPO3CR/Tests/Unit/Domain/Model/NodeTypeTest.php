<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the "NodeType" domain model
 *
 */
class NodeTypeTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function aNodeTypeHasAName() {
		$nodeType = new \TYPO3\TYPO3CR\Domain\Model\NodeType('TYPO3.Neos:Text', array(), array());
		$this->assertSame('TYPO3.Neos:Text', $nodeType->getName());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setDeclaredSuperTypesExpectsAnArrayOfNodeTypes() {
		$folderType = new \TYPO3\TYPO3CR\Domain\Model\NodeType('TYPO3CR:Folder', array('foo'), array());
	}

	/**
	 * @test
	 */
	public function nodeTypesCanHaveAnyNumberOfSuperTypes() {
		$baseType = new \TYPO3\TYPO3CR\Domain\Model\NodeType('TYPO3.TYPO3CR:Base', array(), array());

		$folderType = new \TYPO3\TYPO3CR\Domain\Model\NodeType('TYPO3.TYPO3CR:Folder', array($baseType), array());

		$hideableNodeType = new \TYPO3\TYPO3CR\Domain\Model\NodeType('TYPO3.Neos:HideableContent', array(), array());
		$pageType = new \TYPO3\TYPO3CR\Domain\Model\NodeType('TYPO3.Neos:Page', array($folderType, $hideableNodeType), array());

		$this->assertEquals(array($folderType, $hideableNodeType), $pageType->getDeclaredSuperTypes());

		$this->assertTrue($pageType->isOfType('TYPO3.Neos:Page'));
		$this->assertTrue($pageType->isOfType('TYPO3.Neos:HideableContent'));
		$this->assertTrue($pageType->isOfType('TYPO3.TYPO3CR:Folder'));
		$this->assertTrue($pageType->isOfType('TYPO3.TYPO3CR:Base'));
		$this->assertFalse($pageType->isOfType('TYPO3.TYPO3CR:Exotic'));
	}

	/**
	 * @test
	 */
	public function labelIsEmptyStringByDefault() {
		$baseType = new \TYPO3\TYPO3CR\Domain\Model\NodeType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertSame('', $baseType->getLabel());
	}

	/**
	 * @test
	 */
	public function propertiesAreEmptyArrayByDefault() {
		$baseType = new \TYPO3\TYPO3CR\Domain\Model\NodeType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertSame(array(), $baseType->getProperties());
	}

	/**
	 * @test
	 */
	public function configurationCanBeReturnedViaMagicGetter() {
		$baseType = new \TYPO3\TYPO3CR\Domain\Model\NodeType('TYPO3.TYPO3CR:Base', array(), array(
			'someKey' => 'someValue'
		));
		$this->assertTrue($baseType->hasSomeKey());
		$this->assertSame('someValue', $baseType->getSomeKey());
	}

	/**
	 * @test
	 */
	public function magicHasReturnsFalseIfPropertyDoesNotExist() {
		$baseType = new \TYPO3\TYPO3CR\Domain\Model\NodeType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertFalse($baseType->hasFooKey());
	}
}
?>