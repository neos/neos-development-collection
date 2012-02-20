<?php
namespace TYPO3\TypoScript\Tests\Unit;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript Object Factory
 *
 */
class ObjectFactoryTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function createByNameCreatesANodeTypoScriptObject() {
		$expectedTypoScriptObject = $this->getMock('TYPO3\TypoScript\ObjectInterface');

		$objectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$objectManager->expects($this->once())->method('get')->with('TYPO3\TYPO3\TypoScript\Node')->will($this->returnValue($expectedTypoScriptObject));

		$objectFactory = $this->getAccessibleMock('TYPO3\TypoScript\ObjectFactory', array('dummy'));
		$objectFactory->_set('objectManager', $objectManager);

		$this->assertSame($expectedTypoScriptObject, $objectFactory->createByName('Node'));
	}

	/**
	 * @test
	 */
	public function createByNodeCreatesANodeTypoScriptObjectAndSetsTheNodeOnIt() {
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$expectedTypoScriptObject = $this->getMock('TYPO3\TypoScript\ObjectInterface');
		$expectedTypoScriptObject->expects($this->once())->method('setNode')->with($node);

		$objectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$objectManager->expects($this->once())->method('get')->with('TYPO3\TYPO3\TypoScript\Node')->will($this->returnValue($expectedTypoScriptObject));

		$objectFactory = $this->getAccessibleMock('TYPO3\TypoScript\ObjectFactory', array('getTypoScriptObjectNameByNode'));
		$objectFactory->expects($this->once())->method('getTypoScriptObjectNameByNode')->with($node)->will($this->returnValue('TYPO3\TYPO3\TypoScript\Node'));
		$objectFactory->_set('objectManager', $objectManager);

		$this->assertSame($expectedTypoScriptObject, $objectFactory->createByNode($node));
	}

	/**
	 * @test
	 * @dataProvider unsupportedContentTypes
	 */
	public function getTypoScriptObjectNameByNodeReturnsNodeAsTypoScriptObjectNameIfNoSpecializedTypoScriptObjectExistsForTheContentType($contentTypeName) {
		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$node->expects($this->once())->method('getContentType')->will($this->returnValue($contentTypeName));

		$contentType = new \TYPO3\TYPO3CR\Domain\Model\ContentType($contentTypeName, array(), array());

		$mockContentTypeManager  = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContentTypeManager');
		$mockContentTypeManager->expects($this->any())->method('getContentType')->with($contentTypeName)->will($this->returnValue($contentType));

		$objectFactory = $this->getAccessibleMock('TYPO3\TypoScript\ObjectFactory', array('getObjectNameByContentType'));
		$objectFactory->expects($this->once())->method('getObjectNameByContentType')->with($contentType)->will($this->returnValue(NULL));
		$objectFactory->_set('contentTypeManager', $mockContentTypeManager);

		$this->assertEquals('TYPO3\TYPO3\TypoScript\Node', $objectFactory->getTypoScriptObjectNameByNode($node));
	}

	/**
	 * @return array
	 */
	public function unsupportedContentTypes() {
		return array(
			array('unstructured'),
			array('TYPO3.TYPO3:Googolplex'),
			array('urks'),
			array('Drupal:Node'),
			array('-')
		);
	}

	/**
	 * @test
	 */
	public function getTypoScriptObjectNameByNodeReturnsASpecializedTypoScriptObjectNameIfASpecializedTypoScriptObjectExistsForTheContentType() {
		$contentTypeName = 'TYPO3.TYPO3:ContensUniversalis';

		$node = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$node->expects($this->once())->method('getContentType')->will($this->returnValue($contentTypeName));

		$contentType = new \TYPO3\TYPO3CR\Domain\Model\ContentType($contentTypeName, array(), array());

		$mockContentTypeManager  = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContentTypeManager');
		$mockContentTypeManager->expects($this->any())->method('getContentType')->with($contentTypeName)->will($this->returnValue($contentType));

		$objectFactory = $this->getAccessibleMock('TYPO3\TypoScript\ObjectFactory', array('getObjectNameByContentType'));
		$objectFactory->expects($this->once())->method('getObjectNameByContentType')->with($contentType)->will($this->returnValue('TYPO3\TYPO3\TypoScript\ContensUniversalis'));
		$objectFactory->_set('contentTypeManager', $mockContentTypeManager);

		$this->assertEquals('TYPO3\TYPO3\TypoScript\ContensUniversalis', $objectFactory->getTypoScriptObjectNameByNode($node));
	}

	/**
	 * @return array
	 */
	public function contentTypeHierarchy() {
		return array(
			array('foo:quark', FALSE, NULL, array()),
			array('TYPO3.TYPO3:Page', TRUE, 'TYPO3\TYPO3\TypoScript\Page', array()),
		);
	}

	/**
	 * @test
	 * @dataProvider contentTypeHierarchy
	 */
	public function getObjectNameByContentTypeReturnsTheExpectedContentType($contentTypeName, $possibleObjectNameIsRegistered, $expectedObjectName) {
		$contentType = new \TYPO3\TYPO3CR\Domain\Model\ContentType($contentTypeName, array(), array());

		$objectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$objectManager->expects($this->any())->method('isRegistered')->will($this->returnValue($possibleObjectNameIsRegistered));

		$objectFactory = $this->getAccessibleMock('TYPO3\TypoScript\ObjectFactory', array('dummy'));
		$objectFactory->_set('objectManager', $objectManager);

		$this->assertEquals($expectedObjectName, $objectFactory->_call('getObjectNameByContentType', $contentType));
	}

	/**
	 * @test
	 */
	public function getObjectNameByContentTypeReturnsTheExpectedContentTypeHierarchy() {
		$contentTypeName = 'TYPO3.TYPO3:SubPage';
		$expectedObjectName = 'TYPO3\TYPO3\TypoScript\Page';

		$superType = new \TYPO3\TYPO3CR\Domain\Model\ContentType('TYPO3.TYPO3:Page', array(), array());
		$contentType = new \TYPO3\TYPO3CR\Domain\Model\ContentType($contentTypeName, array($superType), array());

		$objectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$objectManager->expects($this->exactly(2))->method('isRegistered')->will($this->onConsecutiveCalls(FALSE, TRUE));

		$objectFactory = $this->getAccessibleMock('TYPO3\TypoScript\ObjectFactory', array('dummy'));
		$objectFactory->_set('objectManager', $objectManager);

		$this->assertEquals($expectedObjectName, $objectFactory->_call('getObjectNameByContentType', $contentType));
	}

}

?>