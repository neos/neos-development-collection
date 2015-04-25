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

use TYPO3\TYPO3CR\Domain\Model\NodeType;

/**
 * Testcase for the "NodeType" domain model
 *
 */
class NodeTypeTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * example node types
	 *
	 * @var array
	 */
	protected $nodeTypesFixture = array(
		'TYPO3.TYPO3CR.Testing:ContentObject' => array(
			'ui' => array(
				'label' => 'Abstract content object',
			),
			'abstract' => TRUE,
			'properties' => array(
				'_hidden' => array(
					'type' => 'boolean',
					'label' => 'Hidden',
					'category' => 'visibility',
					'priority' => 1
				),
			),
			'propertyGroups' => array(
				'visibility' => array(
					'label' => 'Visibility',
					'priority' => 1
				)
			)
		),
		'TYPO3.TYPO3CR.Testing:Text' => array(
			'superTypes' => array('TYPO3.TYPO3CR.Testing:ContentObject'),
			'ui' => array(
				'label' => 'Text',
			),
			'properties' => array(
				'headline' => array(
					'type' => 'string',
					'placeholder' => 'Enter headline here'
				),
				'text' => array(
					'type' => 'string',
					'placeholder' => '<p>Enter text here</p>'
				)
			),
			'inlineEditableProperties' => array('headline', 'text')
		),
		'TYPO3.TYPO3CR.Testing:Document' => array(
			'superTypes' => array('TYPO3.TYPO3CR.Testing.SomeMixin' => 'TYPO3.TYPO3CR.Testing:SomeMixin'),
			'abstract' => TRUE,
			'aggregate' => TRUE
		),
		'TYPO3.TYPO3CR.Testing:SomeMixin' => array(
			'ui' => array(
				'label' => 'could contain an inspector tab',
			),
			'properties' => array(
				'someMixinProperty' => array(
					'type' => 'string',
					'label' => 'Important hint'
				)
			)
		),
		'TYPO3.TYPO3CR.Testing:Shortcut' => array(
			'superTypes' => array('TYPO3.TYPO3CR.Testing:Document', 'TYPO3.TYPO3CR.Testing.SomeMixin' => NULL),
			'ui' => array(
				'label' => 'Shortcut',
			),
			'properties' => array(
				'target' => array(
					'type' => 'string'
				)
			)
		)
	);

	/**
	 * @test
	 */
	public function aNodeTypeHasAName() {
		$nodeType = new NodeType('TYPO3.TYPO3CR.Testing:Text', array(), array());
		$this->assertSame('TYPO3.TYPO3CR.Testing:Text', $nodeType->getName());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setDeclaredSuperTypesExpectsAnArrayOfNodeTypes() {
		$folderType = new NodeType('TYPO3CR:Folder', array('foo'), array());
	}

	/**
	 * @test
	 */
	public function nodeTypesCanHaveAnyNumberOfSuperTypes() {
		$baseType = new NodeType('TYPO3.TYPO3CR:Base', array(), array());

		$folderType = new NodeType('TYPO3.TYPO3CR.Testing:Document', array($baseType), array());

		$hideableNodeType = new NodeType('TYPO3.TYPO3CR.Testing:HideableContent', array(), array());
		$pageType = new NodeType('TYPO3.TYPO3CR.Testing:Page', array($folderType, $hideableNodeType), array());

		$this->assertEquals(array($folderType, $hideableNodeType), $pageType->getDeclaredSuperTypes());

		$this->assertTrue($pageType->isOfType('TYPO3.TYPO3CR.Testing:Page'));
		$this->assertTrue($pageType->isOfType('TYPO3.TYPO3CR.Testing:HideableContent'));
		$this->assertTrue($pageType->isOfType('TYPO3.TYPO3CR.Testing:Document'));
		$this->assertTrue($pageType->isOfType('TYPO3.TYPO3CR:Base'));

		$this->assertFalse($pageType->isOfType('TYPO3.TYPO3CR:Exotic'));
	}

	/**
	 * @test
	 */
	public function labelIsEmptyStringByDefault() {
		$baseType = new NodeType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertSame('', $baseType->getLabel());
	}

	/**
	 * @test
	 */
	public function propertiesAreEmptyArrayByDefault() {
		$baseType = new NodeType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertSame(array(), $baseType->getProperties());
	}

	/**
	 * @test
	 */
	public function hasConfigurationInitializesTheNodeType() {
		$nodeType = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeType', array('initialize'), array(), '', FALSE);
		$nodeType->expects($this->once())->method('initialize');
		$nodeType->hasConfiguration('foo');
	}

	/**
	 * @test
	 */
	public function hasConfigurationReturnsTrueIfSpecifiedConfigurationPathExists() {
		$nodeType = new NodeType('TYPO3.TYPO3CR:Base', array(), array(
			'someKey' => array(
				'someSubKey' => 'someValue'
			)
		));
		$this->assertTrue($nodeType->hasConfiguration('someKey.someSubKey'));
	}

	/**
	 * @test
	 */
	public function hasConfigurationReturnsFalseIfSpecifiedConfigurationPathDoesNotExist() {
		$nodeType = new NodeType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertFalse($nodeType->hasConfiguration('some.nonExisting.path'));
	}

	/**
	 * @test
	 */
	public function getConfigurationInitializesTheNodeType() {
		$nodeType = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeType', array('initialize'), array(), '', FALSE);
		$nodeType->expects($this->once())->method('initialize');
		$nodeType->getConfiguration('foo');
	}

	/**
	 * @test
	 */
	public function getConfigurationReturnsTheConfigurationWithTheSpecifiedPath() {
		$nodeType = new NodeType('TYPO3.TYPO3CR:Base', array(), array(
			'someKey' => array(
				'someSubKey' => 'someValue'
			)
		));
		$this->assertSame('someValue', $nodeType->getConfiguration('someKey.someSubKey'));
	}

	/**
	 * @test
	 */
	public function getConfigurationReturnsNullIfTheSpecifiedPathDoesNotExist() {
		$nodeType = new NodeType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertNull($nodeType->getConfiguration('some.nonExisting.path'));
	}

	/**
	 * data source for accessingConfigurationOptionsInitializesTheNodeType()
	 */
	public function gettersThatRequiresInitialization() {
		return array(
			array('getFullConfiguration'),
			array('getLabel'),
			array('getNodeLabelGenerator'),
			array('getProperties'),
			array('getDefaultValuesForProperties'),
			array('getAutoCreatedChildNodes'),
		);
	}

	/**
	 * @param string  $getter
	 * @test
	 * @dataProvider gettersThatRequiresInitialization
	 */
	public function accessingConfigurationOptionsInitializesTheNodeType($getter) {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$nodeType = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeType', array('initialize'), array(), '', FALSE);
		$nodeType->_set('objectManager', $mockObjectManager);
		$nodeType->expects($this->atLeastOnce())->method('initialize');
		$nodeType->$getter();
	}

	/**
	 * Tests for the deprecated __call method to verify backwards compatibility
	 */

	/**
	 * @test
	 */
	public function configurationCanBeReturnedViaMagicGetter() {
		$baseType = new NodeType('TYPO3.TYPO3CR:Base', array(), array(
			'someKey' => 'someValue'
		));
		$this->assertTrue($baseType->hasSomeKey());
		$this->assertSame('someValue', $baseType->getSomeKey());
	}

	/**
	 * @test
	 */
	public function magicHasReturnsFalseIfPropertyDoesNotExist() {
		$baseType = new NodeType('TYPO3.TYPO3CR:Base', array(), array());
		$this->assertFalse($baseType->hasFooKey());
	}

	/**
	 * @test
	 */
	public function magicGettersInitializesTheNodeType() {
		$nodeType = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Model\NodeType', array('initialize'), array(), '', FALSE);
		$nodeType->_set('configuration', array('someProperty' => 'someValue'));
		$nodeType->expects($this->once())->method('initialize');
		$nodeType->getSomeProperty();
	}

	/**
	 * @test
	 */
	public function defaultValuesForPropertiesHandlesDateTypes() {
		$nodeType = new NodeType('TYPO3.TYPO3CR:Base', array(), array(
			'properties' => array(
				'date' => array(
					'type' => 'date',
					'defaultValue' => '2014-09-23'
				)
			)
		));

		$this->assertEquals($nodeType->getDefaultValuesForProperties(), array('date' => new \DateTime('2014-09-23')));
	}

	/**
	 * @test
	 */
	public function nodeTypeConfigurationIsMergedTogether() {
		$nodeType = $this->getNodeType('TYPO3.TYPO3CR.Testing:Text');
		$this->assertSame('Text', $nodeType->getLabel());

		$expectedProperties = array(
			'_hidden' => array(
				'type' => 'boolean',
				'label' => 'Hidden',
				'category' => 'visibility',
				'priority' => 1
			),
			'headline' => array(
				'type' => 'string',
				'placeholder' => 'Enter headline here'
			),
			'text' => array(
				'type' => 'string',
				'placeholder' => '<p>Enter text here</p>'
			)
		);
		$this->assertSame($expectedProperties, $nodeType->getProperties());
	}

	/**
	 * This test asserts that a supertype that has been inherited can be removed on a specific type again.
	 * @test
	 */
	public function inheritedSuperTypesCanBeRemoved() {
		$nodeType = $this->getNodeType('TYPO3.TYPO3CR.Testing:Shortcut');
		$this->assertSame('Shortcut', $nodeType->getLabel());

		$expectedProperties = array(
			'target' => array(
				'type' => 'string'
			)
		);
		$this->assertSame($expectedProperties, $nodeType->getProperties());
	}

	/**
	 * Return a nodetype built from the nodeTypesFixture
	 *
	 * @param string $nodeTypeName
	 * @return null|NodeType
	 */
	protected function getNodeType($nodeTypeName) {
		if (!isset($this->nodeTypesFixture[$nodeTypeName])) {
			return NULL;
		}

		$configuration = $this->nodeTypesFixture[$nodeTypeName];
		$declaredSuperTypes = array();
		if (isset($configuration['superTypes']) && is_array($configuration['superTypes'])) {
			foreach ($configuration['superTypes'] as $key => $superTypeName) {
				$declaredSuperTypes[$key] = $this->getNodeType($superTypeName);
			}
		}

		return new NodeType($nodeTypeName, $declaredSuperTypes, $configuration);
	}

	/**
	 * @test
	 */
	public function getAutoCreatedChildNodesReturnsLowercasePaths() {
		$childNodeConfiguration = array('type' => 'TYPO3.TYPO3CR:Base');
		$baseType = new NodeType('TYPO3.TYPO3CR:Base', array(), array(
			'childNodes' => array('nodeName' => $childNodeConfiguration)
		));
		$mockNodeTypeManager = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager')->disableOriginalConstructor()->getMock();
		$mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnValue($baseType));
		$this->inject($baseType, 'nodeTypeManager', $mockNodeTypeManager);
	}

}
