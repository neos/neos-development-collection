<?php
namespace TYPO3\Neos\Tests\Functional\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for the NodeTypeSchemaBuilder
 */
class NodeTypeSchemaBuilderTest extends FunctionalTestCase {

	/**
	 * @var \TYPO3\Neos\Service\NodeTypeSchemaBuilder
	 */
	protected $nodeTypeSchemaBuilder;

	/**
	 * The test schema
	 *
	 * @var array
	 */
	protected $schema;

	public function setUp() {
		parent::setUp();
		$this->nodeTypeSchemaBuilder = $this->objectManager->get('TYPO3\Neos\Service\NodeTypeSchemaBuilder');
		$this->schema = $this->nodeTypeSchemaBuilder->generateNodeTypeSchema();
	}

	/**
	 * @test
	 */
	public function inheritanceMapContainsTransitiveSubTypes() {
		$this->assertTrue(array_key_exists('TYPO3.Neos.BackendSchemaControllerTest:Document', $this->schema['inheritanceMap']['subTypes']), 'Document must be found in InheritanceMap');
		$expectedSubTypesOfDocument = array(
			'TYPO3.Neos.BackendSchemaControllerTest:Page',
			'TYPO3.Neos.BackendSchemaControllerTest:SubPage',
			'TYPO3.Neos.BackendSchemaControllerTest:Folder'
		);

		$this->assertEquals($expectedSubTypesOfDocument, $this->schema['inheritanceMap']['subTypes']['TYPO3.Neos.BackendSchemaControllerTest:Document']);
	}

	/**
	 * @test
	 */
	public function nodeTypesContainCorrectSuperTypes() {
		$this->assertTrue(array_key_exists('TYPO3.Neos.BackendSchemaControllerTest:AlohaNodeType', $this->schema['nodeTypes']), 'AlohaNodeType');

		$expectedSchema = array(
			'superTypes' => array('TYPO3.Neos.BackendSchemaControllerTest:ParentAlohaNodeType' => TRUE),
			'properties' => array(
				'text' => array(
					'ui' => array(
						'aloha' => array(
							'fallbackCase' => array('defined', 'as', 'plain', 'array'),
							'sampleCase' => array('h3', 'sup')
						)
					)
				)
			),
			'label' => ''
		);

		$this->assertEquals($expectedSchema, $this->schema['nodeTypes']['TYPO3.Neos.BackendSchemaControllerTest:AlohaNodeType']);
	}

	/**
	 * @test
	 */
	public function theNodeTypeSchemaIncludesSubTypesInheritanceMap() {
		$subTypesDefinition = $this->schema['inheritanceMap']['subTypes'];

		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:Document', $subTypesDefinition['TYPO3.Neos.BackendSchemaControllerTest:Node']);
		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:Content', $subTypesDefinition['TYPO3.Neos.BackendSchemaControllerTest:Node']);
		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:Page', $subTypesDefinition['TYPO3.Neos.BackendSchemaControllerTest:Node']);
		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:SubPage', $subTypesDefinition['TYPO3.Neos.BackendSchemaControllerTest:Node']);
		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:Text', $subTypesDefinition['TYPO3.Neos.BackendSchemaControllerTest:Node']);
	}

	/**
	 * @test
	 */
	public function alohaUiConfigurationPartsAreActualArrayAndDontContainExcludedElements() {
		$alohaConfiguration = $this->schema['nodeTypes']['TYPO3.Neos.BackendSchemaControllerTest:AlohaNodeType']['properties']['text']['ui']['aloha'];
		$this->assertInternalType('array', $alohaConfiguration['fallbackCase']);
		$this->assertInternalType('array', $alohaConfiguration['sampleCase']);

		$this->assertArrayNotHasKey('h3', $alohaConfiguration['sampleCase']);
		$this->assertArrayNotHasKey('sup', $alohaConfiguration['sampleCase']);
		$this->assertArrayNotHasKey('shouldBeExcluded',  $alohaConfiguration['sampleCase']);

		$this->assertEquals(array('defined', 'as', 'plain', 'array'), $alohaConfiguration['fallbackCase']);
		$this->assertEquals(array('h3', 'sup'), $alohaConfiguration['sampleCase']);
	}

	/**
	 * @test
	 */
	public function constraintsAreEvaluatedForANodeType() {
		$expectedConstraints = array(
			'nodeTypes' => array(
				'TYPO3.Neos.BackendSchemaControllerTest:SubPage' => TRUE
			),
			'childNodes' => array()
		);
		$this->assertEquals($expectedConstraints, $this->schema['constraints']['TYPO3.Neos.BackendSchemaControllerTest:Page']);
	}

	/**
	 * @test
	 */
	public function constraintsForNamedChildNodeTypesAreEvaluatedForANodeType() {
		$this->assertFalse(array_key_exists('TYPO3.Neos.BackendSchemaControllerTest:SubPage', $this->schema['constraints']['TYPO3.Neos.BackendSchemaControllerTest:TwoColumn']['childNodes']['column1']['nodeTypes']));
		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:AlohaNodeType', $this->schema['constraints']['TYPO3.Neos.BackendSchemaControllerTest:TwoColumn']['childNodes']['column1']['nodeTypes']);
	}
}