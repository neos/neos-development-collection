<?php
namespace TYPO3\Neos\Tests\Functional\Controller\Backend;

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
 */
class SchemaControllerTest extends FunctionalTestCase {

	/**
	 * @var array
	 */
	protected $decodedJson;

	public function setUp() {
		parent::setUp();
		$result = $this->browser->request('http://localhost/neos/schema/node-type');
		$this->assertEquals(200, $result->getStatusCode());
		$this->assertEquals('application/json', $result->getHeader('Content-Type'));
		$this->decodedJson = json_decode($result->getContent(), FALSE); //intentionally not Assoc to check for the correct type like JavaScript would do
		$this->assertInstanceOf('stdClass', $this->decodedJson);
		$this->assertObjectHasAttribute('TYPO3.Neos.BackendSchemaControllerTest:AlohaNodeType', $this->decodedJson->nodeTypes);
		$this->assertObjectHasAttribute('inheritanceMap', $this->decodedJson);
	}

	/**
	 * @test
	 */
	public function alohaUiConfigurationPartsAreActualArrayAndDontContainExcludedElements() {
		$alohaConfiguration = $this->decodedJson->nodeTypes->{'TYPO3.Neos.BackendSchemaControllerTest:AlohaNodeType'}->properties->text->ui->aloha;
		$this->assertInternalType('array', $alohaConfiguration->fallbackCase);
		$this->assertInternalType('array', $alohaConfiguration->sampleCase);

		$this->assertArrayNotHasKey('h3', $alohaConfiguration->sampleCase);
		$this->assertArrayNotHasKey('sup', $alohaConfiguration->sampleCase);
		$this->assertArrayNotHasKey('shouldBeExcluded',  $alohaConfiguration->sampleCase);

		$this->assertEquals(array('defined', 'as', 'plain', 'array'), $alohaConfiguration->fallbackCase);
		$this->assertEquals(array('h3', 'sup'), $alohaConfiguration->sampleCase);
	}

	/**
	 * @test
	 */
	public function theNodeTypeSchemaIncludesSubTypesInheritanceMap() {
		$subTypesDefinition = $this->decodedJson->inheritanceMap->subTypes;

		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:Document', $subTypesDefinition->{'TYPO3.Neos.BackendSchemaControllerTest:Node'});
		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:Content', $subTypesDefinition->{'TYPO3.Neos.BackendSchemaControllerTest:Node'});
		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:Page', $subTypesDefinition->{'TYPO3.Neos.BackendSchemaControllerTest:Node'});
		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:SubPage', $subTypesDefinition->{'TYPO3.Neos.BackendSchemaControllerTest:Node'});
		$this->assertContains('TYPO3.Neos.BackendSchemaControllerTest:Text', $subTypesDefinition->{'TYPO3.Neos.BackendSchemaControllerTest:Node'});
	}
}
