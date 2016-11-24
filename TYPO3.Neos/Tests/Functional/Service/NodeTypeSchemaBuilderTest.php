<?php
namespace Neos\Neos\Tests\Functional\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Neos\Service\NodeTypeSchemaBuilder;

/**
 * Testcase for the NodeTypeSchemaBuilder
 */
class NodeTypeSchemaBuilderTest extends FunctionalTestCase
{
    /**
     * @var NodeTypeSchemaBuilder
     */
    protected $nodeTypeSchemaBuilder;

    /**
     * The test schema
     *
     * @var array
     */
    protected $schema;

    public function setUp()
    {
        parent::setUp();
        $this->nodeTypeSchemaBuilder = $this->objectManager->get(NodeTypeSchemaBuilder::class);
        $this->schema = $this->nodeTypeSchemaBuilder->generateNodeTypeSchema();
    }

    /**
     * @test
     */
    public function inheritanceMapContainsTransitiveSubTypes()
    {
        $this->assertTrue(array_key_exists('Neos.Neos.BackendSchemaControllerTest:Document', $this->schema['inheritanceMap']['subTypes']), 'Document must be found in InheritanceMap');
        $expectedSubTypesOfDocument = array(
            'Neos.Neos.BackendSchemaControllerTest:Page',
            'Neos.Neos.BackendSchemaControllerTest:SubPage',
            'Neos.Neos.BackendSchemaControllerTest:Folder'
        );

        $this->assertEquals($expectedSubTypesOfDocument, $this->schema['inheritanceMap']['subTypes']['Neos.Neos.BackendSchemaControllerTest:Document']);
    }

    /**
     * @test
     */
    public function nodeTypesContainCorrectSuperTypes()
    {
        $this->assertTrue(array_key_exists('Neos.Neos.BackendSchemaControllerTest:AlohaNodeType', $this->schema['nodeTypes']), 'AlohaNodeType');

        $expectedSuperTypes = array('Neos.Neos.BackendSchemaControllerTest:ParentAlohaNodeType' => true);
        $expectedPropertyConfiguration = array(
            'fallbackCase' => array('defined', 'as', 'plain', 'array'),
            'sampleCase' => array('h3', 'sup')
        );

        $this->assertEquals($expectedSuperTypes, $this->schema['nodeTypes']['Neos.Neos.BackendSchemaControllerTest:AlohaNodeType']['superTypes']);
        $this->assertEquals($expectedPropertyConfiguration, $this->schema['nodeTypes']['Neos.Neos.BackendSchemaControllerTest:AlohaNodeType']['properties']['text']['ui']['aloha']);
    }

    /**
     * @test
     */
    public function theNodeTypeSchemaIncludesSubTypesInheritanceMap()
    {
        $subTypesDefinition = $this->schema['inheritanceMap']['subTypes'];

        $this->assertContains('Neos.Neos.BackendSchemaControllerTest:Document', $subTypesDefinition['Neos.Neos.BackendSchemaControllerTest:Node']);
        $this->assertContains('Neos.Neos.BackendSchemaControllerTest:Content', $subTypesDefinition['Neos.Neos.BackendSchemaControllerTest:Node']);
        $this->assertContains('Neos.Neos.BackendSchemaControllerTest:Page', $subTypesDefinition['Neos.Neos.BackendSchemaControllerTest:Node']);
        $this->assertContains('Neos.Neos.BackendSchemaControllerTest:SubPage', $subTypesDefinition['Neos.Neos.BackendSchemaControllerTest:Node']);
        $this->assertContains('Neos.Neos.BackendSchemaControllerTest:Text', $subTypesDefinition['Neos.Neos.BackendSchemaControllerTest:Node']);
    }

    /**
     * @test
     */
    public function alohaUiConfigurationPartsAreActualArrayAndDontContainExcludedElements()
    {
        $alohaConfiguration = $this->schema['nodeTypes']['Neos.Neos.BackendSchemaControllerTest:AlohaNodeType']['properties']['text']['ui']['aloha'];
        $this->assertInternalType('array', $alohaConfiguration['fallbackCase']);
        $this->assertInternalType('array', $alohaConfiguration['sampleCase']);

        $this->assertArrayNotHasKey('h3', $alohaConfiguration['sampleCase']);
        $this->assertArrayNotHasKey('sup', $alohaConfiguration['sampleCase']);
        $this->assertArrayNotHasKey('shouldBeExcluded', $alohaConfiguration['sampleCase']);

        $this->assertEquals(array('defined', 'as', 'plain', 'array'), $alohaConfiguration['fallbackCase']);
        $this->assertEquals(array('h3', 'sup'), $alohaConfiguration['sampleCase']);
    }

    /**
     * @test
     */
    public function constraintsAreEvaluatedForANodeType()
    {
        $expectedConstraints = array(
            'nodeTypes' => array(
                'Neos.Neos.BackendSchemaControllerTest:SubPage' => true
            ),
            'childNodes' => array()
        );
        $this->assertEquals($expectedConstraints, $this->schema['constraints']['Neos.Neos.BackendSchemaControllerTest:Page']);
    }

    /**
     * @test
     */
    public function constraintsForNamedChildNodeTypesAreEvaluatedForANodeType()
    {
        $this->assertFalse(array_key_exists('Neos.Neos.BackendSchemaControllerTest:SubPage', $this->schema['constraints']['Neos.Neos.BackendSchemaControllerTest:TwoColumn']['childNodes']['column1']['nodeTypes']));
        $this->assertContains('Neos.Neos.BackendSchemaControllerTest:AlohaNodeType', $this->schema['constraints']['Neos.Neos.BackendSchemaControllerTest:TwoColumn']['childNodes']['column1']['nodeTypes']);
    }
}
