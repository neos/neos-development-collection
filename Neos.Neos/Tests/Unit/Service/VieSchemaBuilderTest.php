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

use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Image;
use Neos\Neos\Service\VieSchemaBuilder;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Testcase for the VieSchemaBuilder
 *
 */
class VieSchemaBuilderTest extends UnitTestCase
{
    /**
     * @var VieSchemaBuilder
     */
    protected $vieSchemaBuilder;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * example node types
     *
     * @var array
     */
    protected $nodeTypesFixture = array(
        'Neos.Neos:ContentObject' => array(
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
        'Neos.Neos:MyFinalType' => array(
            'superTypes' => array('Neos.Neos:ContentObject' => true),
            'final' => true
        ),
        'Neos.Neos:AbstractType' => array(
            'superTypes' => array('Neos.Neos:ContentObject' => true),
            'ui' => array(
                'label' => 'Abstract type',
            ),
            'abstract' => true
        ),
        'Neos.Neos:Text' => array(
            'superTypes' => array('Neos.Neos:ContentObject' => true),
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
        'Neos.Neos:TextWithImage' => array(
            'superTypes' => array('Neos.Neos:Text' => true),
            'ui' => array(
                'label' => 'Text with image',
            ),
            'properties' => array(
                'image' => array(
                    'type' => Image::class,
                    'label' => 'Image'
                )
            )
        )
    );

    public function setUp()
    {
        $this->vieSchemaBuilder = $this->getAccessibleMock(VieSchemaBuilder::class, array('dummy'));

        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->with('NodeTypes')->will($this->returnValue($this->nodeTypesFixture));

        $this->nodeTypeManager = $this->getAccessibleMock(NodeTypeManager::class, array('dummy'));
        $this->nodeTypeManager->_set('configurationManager', $mockConfigurationManager);

        $mockCache = $this->getMockBuilder(StringFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects($this->any())->method('get')->willReturn(null);
        $this->nodeTypeManager->_set('fullConfigurationCache', $mockCache);

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

        $this->vieSchemaBuilder->_call('readNodeTypeConfiguration', 'Neos.Neos:TextWithImage', $this->nodeTypeManager->getNodeType('Neos.Neos:TextWithImage'));

        $this->assertEquals(
            array(
                'typo3:Neos.Neos:TextWithImage' => array('typo3:Neos.Neos:Text')
            ),
            $this->vieSchemaBuilder->_get('superTypeConfiguration')
        );
        $this->arrayHasKey('typo3:Neos.Neos:TextWithImage', $this->vieSchemaBuilder->_get('types'));
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
