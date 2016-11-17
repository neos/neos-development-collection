<?php
namespace TYPO3\Neos\Tests\Functional\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the VieSchemaBuilder
 *
 */
class VieSchemaBuilderTest extends UnitTestCase
{
    /**
     * @var \TYPO3\Neos\Service\VieSchemaBuilder
     */
    protected $vieSchemaBuilder;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * example node types
     *
     * @var array
     */
    protected $nodeTypesFixture = array(
        'TYPO3.Neos:ContentObject' => array(
            'ui' => array(
                'label' => 'Abstract content object',
            ),
            'abstract' => true,
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
        'TYPO3.Neos:MyFinalType' => array(
            'superTypes' => array('TYPO3.Neos:ContentObject' => true),
            'final' => true
        ),
        'TYPO3.Neos:AbstractType' => array(
            'superTypes' => array('TYPO3.Neos:ContentObject' => true),
            'ui' => array(
                'label' => 'Abstract type',
            ),
            'abstract' => true
        ),
        'TYPO3.Neos:Text' => array(
            'superTypes' => array('TYPO3.Neos:ContentObject' => true),
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
        'TYPO3.Neos:TextWithImage' => array(
            'superTypes' => array('TYPO3.Neos:Text' => true),
            'ui' => array(
                'label' => 'Text with image',
            ),
            'properties' => array(
                'image' => array(
                    'type' => 'TYPO3\Neos\Domain\Model\Media\Image',
                    'label' => 'Image'
                )
            )
        )
    );

    public function setUp()
    {
        $this->vieSchemaBuilder = $this->getAccessibleMock('TYPO3\Neos\Service\VieSchemaBuilder', array('dummy'));

        $mockConfigurationManager = $this->getMockBuilder('TYPO3\Flow\Configuration\ConfigurationManager')->disableOriginalConstructor()->getMock();
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->with('NodeTypes')->will($this->returnValue($this->nodeTypesFixture));

        $this->nodeTypeManager = $this->getAccessibleMock('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager', array('dummy'));
        $this->nodeTypeManager->_set('configurationManager', $mockConfigurationManager);

        $this->vieSchemaBuilder->_set('nodeTypeManager', $this->nodeTypeManager);
    }

    /**
     * @test
     */
    public function generateVieSchemaReturnsCachedConfigurationIfAvailable()
    {
        $testConfig = array('foo' => 'bar');
        $this->vieSchemaBuilder->_set('configuration', $testConfig);
        $this->assertEquals($testConfig, $this->vieSchemaBuilder->generateVieSchema());
    }

    /**
     * @test
     */
    public function readNodeTypeConfigurationFillsTypeAndPropertyConfiguration()
    {
        $this->assertEquals($this->vieSchemaBuilder->_get('superTypeConfiguration'), array());
        $this->assertEquals($this->vieSchemaBuilder->_get('types'), array());
        $this->assertEquals($this->vieSchemaBuilder->_get('properties'), array());

        $this->vieSchemaBuilder->_call('readNodeTypeConfiguration', 'TYPO3.Neos:TextWithImage', $this->nodeTypeManager->getNodeType('TYPO3.Neos:TextWithImage'));

        $this->assertEquals(
            array(
                'typo3:TYPO3.Neos:TextWithImage' => array('typo3:TYPO3.Neos:Text')
            ),
            $this->vieSchemaBuilder->_get('superTypeConfiguration')
        );
        $this->arrayHasKey('typo3:TYPO3.Neos:TextWithImage', $this->vieSchemaBuilder->_get('types'));
        $this->assertEquals(4, count($this->vieSchemaBuilder->_get('properties')));
    }

    /**
     * @test
     */
    public function generatedVieSchemaMatchesExpectedOutput()
    {
        $schema = $this->vieSchemaBuilder->generateVieSchema();
        $fixtureSchema = file_get_contents(__DIR__ . '/Fixtures/VieSchema.json');
        $this->assertEquals(json_decode($fixtureSchema), json_decode(json_encode($schema)));
    }
}
