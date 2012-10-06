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
 * Testcase for the "ContenType" domain model
 *
 */
class ContentTypeTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function aContentTypeHasAName() {
		$contentType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3.TYPO3:Text', array(), array());
		$this->assertSame('TYPO3.TYPO3:Text', $contentType->getName());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setDeclaredSuperTypesExpectsAnArrayOfContentTypes() {
		$folderType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3CR:Folder', array('foo'), array());
	}

	/**
	 * @test
	 */
	public function contentTypesCanHaveAnyNumberOfSuperTypes() {
		$baseType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3.TYPO3CR:Base', array(), array());

		$folderType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3.TYPO3CR:Folder', array($baseType), array());

		$hideableContentType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3.TYPO3:HideableContent', array(), array());
		$pageType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3.TYPO3:Page', array($folderType, $hideableContentType), array());

		$this->assertEquals(array($folderType, $hideableContentType), $pageType->getDeclaredSuperTypes());

		$this->assertTrue($pageType->isOfType('TYPO3.TYPO3:Page'));
		$this->assertTrue($pageType->isOfType('TYPO3.TYPO3:HideableContent'));
		$this->assertTrue($pageType->isOfType('TYPO3.TYPO3CR:Folder'));
		$this->assertTrue($pageType->isOfType('TYPO3.TYPO3CR:Base'));
		$this->assertFalse($pageType->isOfType('TYPO3.TYPO3CR:Exotic'));
	}

	/**
	 * @test
	 */
	public function labelIsEmptyStringByDefault() {
		$baseType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertSame('', $baseType->getLabel());
	}

	/**
	 * @test
	 */
	public function propertiesAreEmptyArrayByDefault() {
		$baseType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertSame(array(), $baseType->getProperties());
	}

	/**
	 * @test
	 */
	public function configurationCanBeReturnedViaMagicGetter() {
		$baseType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3.TYPO3CR:Base', array(), array(
			'someKey' => 'someValue'
		));
		$this->assertTrue($baseType->hasSomeKey());
		$this->assertSame('someValue', $baseType->getSomeKey());
	}

	/**
	 * @test
	 */
	public function magicHasReturnsFalseIfPropertyDoesNotExist() {
		$baseType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertFalse($baseType->hasFooKey());
	}
}
?>