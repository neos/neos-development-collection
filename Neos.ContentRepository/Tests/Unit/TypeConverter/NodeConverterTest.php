<?php
namespace Neos\ContentRepository\Tests\Unit\TypeConverter;

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
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Asset;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\TypeConverter\NodeConverter;

/**
 * Testcase for the Node TypeConverter
 */
class NodeConverterTest extends UnitTestCase
{
    /**
     * @var NodeConverter
     */
    protected $nodeConverter;

    /**
     * @var NodeDataRepository
     */
    protected $mockNodeDataRepository;

    /**
     * @var WorkspaceRepository
     */
    protected $mockWorkspaceRepository;

    /**
     * @var NodeFactory
     */
    protected $mockNodeFactory;

    /**
     * @var ContextFactoryInterface
     */
    protected $mockContextFactory;

    /**
     * @var PropertyMappingConfigurationInterface
     */
    protected $mockConverterConfiguration;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var PropertyMapper
     */
    protected $mockPropertyMapper;

    public function setUp()
    {
        $this->nodeConverter = new NodeConverter();

        $this->mockContextFactory = $this->getMockBuilder(ContextFactoryInterface::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->nodeConverter, 'contextFactory', $this->mockContextFactory);

        $this->mockPropertyMapper = $this->createMock(PropertyMapper::class);
        $this->inject($this->nodeConverter, 'propertyMapper', $this->mockPropertyMapper);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->inject($this->nodeConverter, 'objectManager', $this->mockObjectManager);

        $this->mockConverterConfiguration = $this->getMockBuilder(PropertyMappingConfigurationInterface::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function convertFromSetsRemovedContentShownContextPropertyFromConfigurationForContextPathSource()
    {
        $contextPath = '/foo/bar@user-demo';
        $nodePath = '/foo/bar';

        $mockNode = $this->setUpNodeWithNodeType($nodePath);

        $this->mockConverterConfiguration->expects($this->any())->method('getConfigurationValue')->with(NodeConverter::class, NodeConverter::REMOVED_CONTENT_SHOWN)->will($this->returnValue(true));

        $result = $this->nodeConverter->convertFrom($contextPath, null, array(), $this->mockConverterConfiguration);
        $this->assertSame($mockNode, $result);

        $contextProperties = $mockNode->getContext()->getProperties();
        $this->assertArrayHasKey('removedContentShown', $contextProperties, 'removedContentShown context property should be set');
        $this->assertTrue($contextProperties['removedContentShown'], 'removedContentShown context property should be TRUE');
    }

    /**
     * @test
     */
    public function convertFromUsesPropertyMapperToConvertNodePropertyOfReferenceType()
    {
        $contextPath = '/foo/bar@user-demo';
        $nodePath = '/foo/bar';
        $nodeTypeProperties = array(
            'reference' => array(
                'type' => 'reference'
            )
        );
        $propertyValue = '8aaf4dd2-bd85-11e3-ae3d-14109fd7a2dd';
        $source = array(
            '__contextNodePath' => $contextPath,
            'reference' => $propertyValue
        );

        $convertedPropertyValue = new \stdClass();

        $mockNode = $this->setUpNodeWithNodeType($nodePath, $nodeTypeProperties);

        $mockNode->getContext()->expects($this->once())->method('getNodeByIdentifier')->with($propertyValue)->will($this->returnValue($convertedPropertyValue));

        $mockNode->expects($this->once())->method('setProperty')->with('reference', $convertedPropertyValue);

        $this->nodeConverter->convertFrom($source, null, array(), $this->mockConverterConfiguration);
    }

    /**
     * @test
     */
    public function convertFromUsesPropertyMapperToConvertNodePropertyOfReferencesType()
    {
        $contextPath = '/foo/bar@user-demo';
        $nodePath = '/foo/bar';
        $nodeTypeProperties = array(
            'references' => array(
                'type' => 'references'
            )
        );
        $decodedPropertyValue = array('8aaf4dd2-bd85-11e3-ae3d-14109fd7a2dd', '8febe94a-bd85-11e3-8401-14109fd7a2dd');
        $source = array(
            '__contextNodePath' => $contextPath,
            'references' => json_encode($decodedPropertyValue)
        );

        $convertedPropertyValue = array(new \stdClass(), new \stdClass());

        $mockNode = $this->setUpNodeWithNodeType($nodePath, $nodeTypeProperties);

        $mockContext = $mockNode->getContext();
        $mockContext->expects($this->at(2))->method('getNodeByIdentifier')->with(current($decodedPropertyValue))->will($this->returnValue(current($convertedPropertyValue)));
        $mockContext->expects($this->at(3))->method('getNodeByIdentifier')->with(end($decodedPropertyValue))->will($this->returnValue(end($convertedPropertyValue)));

        $mockNode->expects($this->once())->method('setProperty')->with('references', $convertedPropertyValue);

        $this->nodeConverter->convertFrom($source, null, array(), $this->mockConverterConfiguration);
    }

    /**
     * @test
     */
    public function convertFromUsesPropertyMapperToConvertNodePropertyOfArrayType()
    {
        $contextPath = '/foo/bar@user-demo';
        $nodePath = '/foo/bar';
        $nodeTypeProperties = array(
            'assets' => array(
                'type' => 'array<Neos\Media\Domain\Model\Asset>'
            )
        );
        $decodedPropertyValue = array('8aaf4dd2-bd85-11e3-ae3d-14109fd7a2dd', '8febe94a-bd85-11e3-8401-14109fd7a2dd');
        $source = array(
            '__contextNodePath' => $contextPath,
            'assets' => json_encode($decodedPropertyValue)
        );

        $convertedPropertyValue = array(new \stdClass(), new \stdClass());

        $mockNode = $this->setUpNodeWithNodeType($nodePath, $nodeTypeProperties);

        $this->mockObjectManager->expects($this->any())->method('isRegistered')->with(Asset::class)->will($this->returnValue(true));

        $this->mockPropertyMapper->expects($this->once())->method('convert')->with($decodedPropertyValue, $nodeTypeProperties['assets']['type'])->will($this->returnValue($convertedPropertyValue));
        $mockNode->expects($this->once())->method('setProperty')->with('assets', $convertedPropertyValue);

        $this->nodeConverter->convertFrom($source, null, array(), $this->mockConverterConfiguration);
    }

    /**
     * @test
     */
    public function convertFromDecodesJsonEncodedArraysAsAssociative()
    {
        $contextPath = '/foo/bar@user-demo';
        $nodePath = '/foo/bar';
        $nodeTypeProperties = array(
            'quux' => array(
                'type' => 'array'
            )
        );
        $decodedPropertyValue = array('foo' => 'bar');
        $source = array(
            '__contextNodePath' => $contextPath,
            'quux' => json_encode($decodedPropertyValue)
        );

        $mockNode = $this->setUpNodeWithNodeType($nodePath, $nodeTypeProperties);

        $mockNode->expects($this->once())->method('setProperty')->with('quux', $decodedPropertyValue);

        $this->nodeConverter->convertFrom($source, null, array(), $this->mockConverterConfiguration);
    }

    /**
     * @param string $nodePath
     * @param array $nodeTypeProperties
     * @return NodeInterface
     */
    protected function setUpNodeWithNodeType($nodePath, $nodeTypeProperties = array())
    {
        $mockLiveWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $mockNode = $this->createMock(NodeInterface::class);
        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->expects($this->any())->method('getProperties')->will($this->returnValue($nodeTypeProperties));
        $mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue($mockNodeType));

        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockLiveWorkspace));
        $mockContext->expects($this->any())->method('getNode')->with($nodePath)->will($this->returnValue($mockNode));

        $mockNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));

        // Simulate context properties by returning the same properties that were given to the ContextFactory
        $this->mockContextFactory->expects($this->any())->method('create')->will($this->returnCallback(function ($contextProperties) use ($mockContext) {
            $mockContext->expects($this->any())->method('getProperties')->will($this->returnValue($contextProperties));
            return $mockContext;
        }));

        return $mockNode;
    }
}
