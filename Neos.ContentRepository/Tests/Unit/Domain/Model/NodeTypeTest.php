<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 * Testcase for the "NodeType" domain model
 *
 */
class NodeTypeTest extends UnitTestCase
{
    /**
     * example node types
     *
     * @var array
     */
    protected $nodeTypesFixture = array(
        'Neos.ContentRepository.Testing:ContentObject' => array(
            'ui' => array(
                'label' => 'Abstract content object'
            ),
            'abstract' => true,
            'properties' => array(
                '_hidden' => array(
                    'type' => 'boolean',
                    'label' => 'Hidden',
                    'category' => 'visibility',
                    'priority' => 1
                )
            ),
            'propertyGroups' => array(
                'visibility' => array(
                    'label' => 'Visibility',
                    'priority' => 1
                )
            )
        ),
        'Neos.ContentRepository.Testing:Text' => array(
            'superTypes' => array('Neos.ContentRepository.Testing:ContentObject' => true),
            'ui' => array(
                'label' => 'Text'
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
        'Neos.ContentRepository.Testing:Document' => array(
            'superTypes' => array('Neos.ContentRepository.Testing:SomeMixin' => true),
            'abstract' => true,
            'aggregate' => true
        ),
        'Neos.ContentRepository.Testing:SomeMixin' => array(
            'ui' => array(
                'label' => 'could contain an inspector tab'
            ),
            'properties' => array(
                'someMixinProperty' => array(
                    'type' => 'string',
                    'label' => 'Important hint'
                )
            )
        ),
        'Neos.ContentRepository.Testing:Shortcut' => array(
            'superTypes' => array(
                'Neos.ContentRepository.Testing:Document' => true,
                'Neos.ContentRepository.Testing:SomeMixin' => false
            ),
            'ui' => array(
                'label' => 'Shortcut'
            ),
            'properties' => array(
                'target' => array(
                    'type' => 'string'
                )
            )
        ),
        'Neos.ContentRepository.Testing:SubShortcut' => array(
            'superTypes' => array(
                'Neos.ContentRepository.Testing:Shortcut' => true
            ),
            'ui' => array(
                'label' => 'Sub-Shortcut'
            )
        ),
        'Neos.ContentRepository.Testing:SubSubShortcut' => array(
            'superTypes' => array(
                'Neos.ContentRepository.Testing:SubShortcut' => true,
                'Neos.ContentRepository.Testing:SomeMixin' => true
            ),
            'ui' => array(
                'label' => 'Sub-Sub-Shortcut'
            )
        ),
        'Neos.ContentRepository.Testing:SubSubSubShortcut' => array(
            'superTypes' => array(
                'Neos.ContentRepository.Testing:SubSubShortcut' => true
            ),
            'ui' => array(
                'label' => 'Sub-Sub-Sub-Shortcut'
            )
        )
    );

    /**
     * @test
     */
    public function aNodeTypeHasAName()
    {
        $nodeType = new NodeType('Neos.ContentRepository.Testing:Text', array(), array());
        $this->assertSame('Neos.ContentRepository.Testing:Text', $nodeType->getName());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setDeclaredSuperTypesExpectsAnArrayOfNodeTypesAsKeys()
    {
        new NodeType('TYPO3CR:Folder', array('foo' => true), array());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setDeclaredSuperTypesAcceptsAnArrayOfNodeTypes()
    {
        new NodeType('TYPO3CR:Folder', array('foo'), array());
    }

    /**
     * @test
     */
    public function nodeTypesCanHaveAnyNumberOfSuperTypes()
    {
        $baseType = new NodeType('Neos.ContentRepository:Base', array(), array());

        $folderType = new NodeType('Neos.ContentRepository.Testing:Document', array($baseType), array());

        $hideableNodeType = new NodeType('Neos.ContentRepository.Testing:HideableContent', array(), array());
        $pageType = new NodeType('Neos.ContentRepository.Testing:Page', array($folderType, $hideableNodeType), array());

        $this->assertEquals(array($folderType, $hideableNodeType), $pageType->getDeclaredSuperTypes());

        $this->assertTrue($pageType->isOfType('Neos.ContentRepository.Testing:Page'));
        $this->assertTrue($pageType->isOfType('Neos.ContentRepository.Testing:HideableContent'));
        $this->assertTrue($pageType->isOfType('Neos.ContentRepository.Testing:Document'));
        $this->assertTrue($pageType->isOfType('Neos.ContentRepository:Base'));

        $this->assertFalse($pageType->isOfType('Neos.ContentRepository:Exotic'));
    }

    /**
     * @test
     */
    public function labelIsEmptyStringByDefault()
    {
        $baseType = new NodeType('Neos.ContentRepository:Base', array(), array());
        $this->assertSame('', $baseType->getLabel());
    }

    /**
     * @test
     */
    public function propertiesAreEmptyArrayByDefault()
    {
        $baseType = new NodeType('Neos.ContentRepository:Base', array(), array());
        $this->assertSame(array(), $baseType->getProperties());
    }

    /**
     * @test
     */
    public function hasConfigurationInitializesTheNodeType()
    {
        $nodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->setMethods(array('initialize'))->getMock();
        $nodeType->expects($this->once())->method('initialize');
        $nodeType->hasConfiguration('foo');
    }

    /**
     * @test
     */
    public function hasConfigurationReturnsTrueIfSpecifiedConfigurationPathExists()
    {
        $nodeType = new NodeType('Neos.ContentRepository:Base', array(), array(
            'someKey' => array(
                'someSubKey' => 'someValue'
            )
        ));
        $this->assertTrue($nodeType->hasConfiguration('someKey.someSubKey'));
    }

    /**
     * @test
     */
    public function hasConfigurationReturnsFalseIfSpecifiedConfigurationPathDoesNotExist()
    {
        $nodeType = new NodeType('Neos.ContentRepository:Base', array(), array());
        $this->assertFalse($nodeType->hasConfiguration('some.nonExisting.path'));
    }

    /**
     * @test
     */
    public function getConfigurationInitializesTheNodeType()
    {
        $nodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->setMethods(array('initialize'))->getMock();
        $nodeType->expects($this->once())->method('initialize');
        $nodeType->getConfiguration('foo');
    }

    /**
     * @test
     */
    public function getConfigurationReturnsTheConfigurationWithTheSpecifiedPath()
    {
        $nodeType = new NodeType('Neos.ContentRepository:Base', array(), array(
            'someKey' => array(
                'someSubKey' => 'someValue'
            )
        ));
        $this->assertSame('someValue', $nodeType->getConfiguration('someKey.someSubKey'));
    }

    /**
     * @test
     */
    public function getConfigurationReturnsNullIfTheSpecifiedPathDoesNotExist()
    {
        $nodeType = new NodeType('Neos.ContentRepository:Base', array(), array());
        $this->assertNull($nodeType->getConfiguration('some.nonExisting.path'));
    }

    /**
     * data source for accessingConfigurationOptionsInitializesTheNodeType()
     */
    public function gettersThatRequiresInitialization()
    {
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
    public function accessingConfigurationOptionsInitializesTheNodeType($getter)
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $nodeType = $this->getAccessibleMock(NodeType::class, array('initialize'), array(), '', false);
        $nodeType->_set('objectManager', $mockObjectManager);
        $nodeType->expects($this->atLeastOnce())->method('initialize');
        $nodeType->$getter();
    }

    /**
     * @test
     */
    public function defaultValuesForPropertiesHandlesDateTypes()
    {
        $nodeType = new NodeType('Neos.ContentRepository:Base', array(), array(
            'properties' => array(
                'date' => array(
                    'type' => 'DateTime',
                    'defaultValue' => '2014-09-23'
                )
            )
        ));

        $this->assertEquals($nodeType->getDefaultValuesForProperties(), array('date' => new \DateTime('2014-09-23')));
    }

    /**
     * @test
     */
    public function nodeTypeConfigurationIsMergedTogether()
    {
        $nodeType = $this->getNodeType('Neos.ContentRepository.Testing:Text');
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
    public function inheritedSuperTypesCanBeRemoved()
    {
        $nodeType = $this->getNodeType('Neos.ContentRepository.Testing:Shortcut');
        $this->assertSame('Shortcut', $nodeType->getLabel());

        $expectedProperties = array(
            'target' => array(
                'type' => 'string'
            )
        );
        $this->assertSame($expectedProperties, $nodeType->getProperties());
    }

    /**
     * This test asserts that a supertype that has been inherited can be removed by a supertype again.
     * @test
     */
    public function inheritedSuperSuperTypesCanBeRemoved()
    {
        $nodeType = $this->getNodeType('Neos.ContentRepository.Testing:SubShortcut');
        $this->assertSame('Sub-Shortcut', $nodeType->getLabel());

        $expectedProperties = array(
            'target' => array(
                'type' => 'string'
            )
        );
        $this->assertSame($expectedProperties, $nodeType->getProperties());
    }

    /**
     * This test asserts that a supertype that has been inherited can be removed by a supertype again.
     * @test
     */
    public function superTypesRemovedByInheritanceCanBeAddedAgain()
    {
        $nodeType = $this->getNodeType('Neos.ContentRepository.Testing:SubSubSubShortcut');
        $this->assertSame('Sub-Sub-Sub-Shortcut', $nodeType->getLabel());

        $expectedProperties = array(
            'target' => array(
                'type' => 'string'
            ),
            'someMixinProperty' => array(
                'type' => 'string',
                'label' => 'Important hint'
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
    protected function getNodeType($nodeTypeName)
    {
        if (!isset($this->nodeTypesFixture[$nodeTypeName])) {
            return null;
        }

        $configuration = $this->nodeTypesFixture[$nodeTypeName];
        $declaredSuperTypes = array();
        if (isset($configuration['superTypes']) && is_array($configuration['superTypes'])) {
            foreach ($configuration['superTypes'] as $superTypeName => $enabled) {
                $declaredSuperTypes[$superTypeName] = $enabled === true ? $this->getNodeType($superTypeName) : null;
            }
        }

        return new NodeType($nodeTypeName, $declaredSuperTypes, $configuration);
    }

    /**
     * @test
     */
    public function getAutoCreatedChildNodesReturnsLowercasePaths()
    {
        $childNodeConfiguration = array('type' => 'Neos.ContentRepository:Base');
        $baseType = new NodeType('Neos.ContentRepository:Base', array(), array(
            'childNodes' => array('nodeName' => $childNodeConfiguration)
        ));
        $mockNodeTypeManager = $this->getMockBuilder(NodeTypeManager::class)->disableOriginalConstructor()->getMock();
        $mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnValue($baseType));
        $this->inject($baseType, 'nodeTypeManager', $mockNodeTypeManager);

        $autoCreatedChildNodes = $mockNodeTypeManager->getNodeType('Neos.ContentRepository:Base')->getAutoCreatedChildNodes();

        $this->assertArrayHasKey('nodename', $autoCreatedChildNodes);
    }
}
