<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service;

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
 * Testcase for the "NodeTypeManager"
 *
 */
class NodeTypeManagerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * example node types
	 *
	 * @var array
	 */
	protected $nodeTypesFixture = array(
		'TYPO3.Neos:ContentObject' => array(
			'label' => 'Abstract content object',
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
		'TYPO3.Neos:Text' => array(
			'superTypes' => array('TYPO3.Neos:ContentObject'),
			'label' => 'Text',
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
		'TYPO3.Neos:TextWithImage' => array(
			'superTypes' => array('TYPO3.Neos:Text'),
			'label' => 'Text with image',
			'properties' => array(
				'image' => array(
					'type' => 'TYPO3\Neos\Domain\Model\Media\Image',
					'label' => 'Image'
				)
			)
		)
	);

	/**
	 * A mock configuration manager
	 *
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	public function setUp() {
		$this->configurationManager = $this->getMockBuilder('TYPO3\Flow\Configuration\ConfigurationManager')
			->disableOriginalConstructor()
			->getMock();
		$this->configurationManager
			->expects($this->any())
			->method('getConfiguration')
			->with('NodeTypes')
			->will($this->returnValue($this->nodeTypesFixture));
	}

	/**
	 * @test
	 */
	public function nodeTypeConfigurationIsMergedTogether() {
		$nodeTypeManager = new \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager();
		$this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

		$nodeType = $nodeTypeManager->getNodeType('TYPO3.Neos:Text');
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
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
	 */
	public function getNodeTypeThrowsExceptionForUnknownNodeType() {
		$nodeTypeManager = new \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager();
		$this->inject($nodeTypeManager, 'configurationManager', $this->configurationManager);

		$nodeTypeManager->getNodeType('TYPO3.Neos:TextFooBarNotHere');
	}
}
?>