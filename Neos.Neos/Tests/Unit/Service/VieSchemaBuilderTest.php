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
    protected $nodeTypesFixture = [
        'Neos.Neos:ContentObject' => [
            'ui' => [
                'label' => 'Abstract content object',
            ],
            'abstract' => true,
            'properties' => [
                '_hidden' => [
                    'type' => 'boolean',
                    'label' => 'Hidden',
                    'category' => 'visibility',
                    'priority' => 1
                ],
            ],
            'propertyGroups' => [
                'visibility' => [
                    'label' => 'Visibility',
                    'priority' => 1
                ]
            ]
        ],
        'Neos.Neos:MyFinalType' => [
            'superTypes' => ['Neos.Neos:ContentObject' => true],
            'final' => true
        ],
        'Neos.Neos:AbstractType' => [
            'superTypes' => ['Neos.Neos:ContentObject' => true],
            'ui' => [
                'label' => 'Abstract type',
            ],
            'abstract' => true
        ],
        'Neos.Neos:Text' => [
            'superTypes' => ['Neos.Neos:ContentObject' => true],
            'ui' => [
                'label' => 'Text',
            ],
            'properties' => [
                'headline' => [
                    'type' => 'string',
                    'placeholder' => 'Enter headline here'
                ],
                'text' => [
                    'type' => 'string',
                    'placeholder' => '<p>Enter text here</p>'
                ]
            ],
            'inlineEditableProperties' => ['headline', 'text']
        ],
        'Neos.Neos:TextWithImage' => [
            'superTypes' => ['Neos.Neos:Text' => true],
            'ui' => [
                'label' => 'Text with image',
            ],
            'properties' => [
                'image' => [
                    'type' => Image::class,
                    'label' => 'Image'
                ]
            ]
        ]
    ];

    public function setUp(): void
    {
        $this->vieSchemaBuilder = $this->getAccessibleMock(VieSchemaBuilder::class, ['dummy']);

        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $mockConfigurationManager->expects(self::any())->method('getConfiguration')->with('NodeTypes')->will(self::returnValue($this->nodeTypesFixture));

        $this->nodeTypeManager = $this->getAccessibleMock(NodeTypeManager::class, ['dummy']);
        $this->nodeTypeManager->_set('configurationManager', $mockConfigurationManager);

        $mockCache = $this->getMockBuilder(StringFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects(self::any())->method('get')->willReturn(null);
        $this->nodeTypeManager->_set('fullConfigurationCache', $mockCache);

        $this->vieSchemaBuilder->_set('nodeTypeManager', $this->nodeTypeManager);
    }

    /**
     * @test
     */
    public function generateVieSchemaReturnsCachedConfigurationIfAvailable()
    {
        $testConfig = ['foo' => 'bar'];
        $this->vieSchemaBuilder->_set('configuration', $testConfig);
        self::assertEquals($testConfig, $this->vieSchemaBuilder->generateVieSchema());
    }

    /**
     * @test
     */
    public function readNodeTypeConfigurationFillsTypeAndPropertyConfiguration()
    {
        self::assertEquals($this->vieSchemaBuilder->_get('superTypeConfiguration'), []);
        self::assertEquals($this->vieSchemaBuilder->_get('types'), []);
        self::assertEquals($this->vieSchemaBuilder->_get('properties'), []);

        $this->vieSchemaBuilder->_call('readNodeTypeConfiguration', 'Neos.Neos:TextWithImage', $this->nodeTypeManager->getNodeType('Neos.Neos:TextWithImage'));

        self::assertEquals(
            [
                'neoscms:Neos.Neos:TextWithImage' => ['neoscms:Neos.Neos:Text']
            ],
            $this->vieSchemaBuilder->_get('superTypeConfiguration')
        );
        $this->arrayHasKey('neoscms:Neos.Neos:TextWithImage', $this->vieSchemaBuilder->_get('types'));
        self::assertEquals(4, count($this->vieSchemaBuilder->_get('properties')));
    }

    /**
     * @test
     */
    public function generatedVieSchemaMatchesExpectedOutput()
    {
        $schema = $this->vieSchemaBuilder->generateVieSchema();
        $fixtureSchema = file_get_contents(__DIR__ . '/Fixtures/VieSchema.json');
        self::assertEquals(json_decode($fixtureSchema), json_decode(json_encode($schema)));
    }
}
