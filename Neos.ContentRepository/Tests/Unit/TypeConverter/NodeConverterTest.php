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
use PHPUnit\Framework\MockObject\MockObject;

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

    public function setUp(): void
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

        $this->mockConverterConfiguration->expects(self::atLeast(2))
            ->method('getConfigurationValue')
            ->withConsecutive([NodeConverter::class, NodeConverter::INVISIBLE_CONTENT_SHOWN], [NodeConverter::class, NodeConverter::REMOVED_CONTENT_SHOWN])
            ->willReturn(true);

        $result = $this->nodeConverter->convertFrom($contextPath, null, [], $this->mockConverterConfiguration);
        self::assertSame($mockNode, $result);

        $contextProperties = $mockNode->getContext()->getProperties();
        self::assertArrayHasKey('removedContentShown', $contextProperties, 'removedContentShown context property should be set');
        self::assertTrue($contextProperties['removedContentShown'], 'removedContentShown context property should be true');
    }

    /**
     * @test
     */
    public function convertFromUsesPropertyMapperToConvertNodePropertyOfReferenceType()
    {
        $contextPath = '/foo/bar@user-demo';
        $nodePath = '/foo/bar';
        $nodeTypeProperties = [
            'reference' => [
                'type' => 'reference'
            ]
        ];
        $propertyValue = '8aaf4dd2-bd85-11e3-ae3d-14109fd7a2dd';
        $source = [
            '__contextNodePath' => $contextPath,
            'reference' => $propertyValue
        ];

        $convertedPropertyValue = new \stdClass();

        $mockNode = $this->setUpNodeWithNodeType($nodePath, $nodeTypeProperties);

        $mockNode->getContext()->expects(self::once())->method('getNodeByIdentifier')->with($propertyValue)->will(self::returnValue($convertedPropertyValue));

        $mockNode->expects(self::once())->method('setProperty')->with('reference', $convertedPropertyValue);

        $this->nodeConverter->convertFrom($source, null, [], $this->mockConverterConfiguration);
    }

    /**
     * @test
     */
    public function convertFromUsesPropertyMapperToConvertNodePropertyOfReferencesType()
    {
        $contextPath = '/foo/bar@user-demo';
        $nodePath = '/foo/bar';
        $nodeTypeProperties = [
            'references' => [
                'type' => 'references'
            ]
        ];
        $decodedPropertyValue = ['8aaf4dd2-bd85-11e3-ae3d-14109fd7a2dd', '8febe94a-bd85-11e3-8401-14109fd7a2dd'];
        $source = [
            '__contextNodePath' => $contextPath,
            'references' => json_encode($decodedPropertyValue)
        ];

        $convertedPropertyValue = [new \stdClass(), new \stdClass()];

        $mockNode = $this->setUpNodeWithNodeType($nodePath, $nodeTypeProperties);

        /** @var Context|MockObject $mockContext */
        $mockContext = $mockNode->getContext();
        $mockContext->expects(self::atLeast(2))
            ->method('getNodeByIdentifier')
            ->withConsecutive([current($decodedPropertyValue)], [end($decodedPropertyValue)])
            ->willReturnOnConsecutiveCalls(current($convertedPropertyValue), end($convertedPropertyValue));

        $mockNode->expects(self::once())->method('setProperty')->with('references', $convertedPropertyValue);

        $this->nodeConverter->convertFrom($source, null, [], $this->mockConverterConfiguration);
    }

    /**
     * @test
     */
    public function convertFromUsesPropertyMapperToConvertNodePropertyOfArrayType()
    {
        $contextPath = '/foo/bar@user-demo';
        $nodePath = '/foo/bar';
        $nodeTypeProperties = [
            'assets' => [
                'type' => 'array<Neos\Media\Domain\Model\Asset>'
            ]
        ];
        $decodedPropertyValue = ['8aaf4dd2-bd85-11e3-ae3d-14109fd7a2dd', '8febe94a-bd85-11e3-8401-14109fd7a2dd'];
        $source = [
            '__contextNodePath' => $contextPath,
            'assets' => json_encode($decodedPropertyValue)
        ];

        $convertedPropertyValue = [new \stdClass(), new \stdClass()];

        $mockNode = $this->setUpNodeWithNodeType($nodePath, $nodeTypeProperties);

        $this->mockObjectManager->expects(self::any())->method('isRegistered')->with(Asset::class)->will(self::returnValue(true));

        $this->mockPropertyMapper->expects(self::once())->method('convert')->with($decodedPropertyValue, $nodeTypeProperties['assets']['type'])->will(self::returnValue($convertedPropertyValue));
        $mockNode->expects(self::once())->method('setProperty')->with('assets', $convertedPropertyValue);

        $this->nodeConverter->convertFrom($source, null, [], $this->mockConverterConfiguration);
    }

    /**
     * @test
     */
    public function convertFromDecodesJsonEncodedArraysAsAssociative()
    {
        $contextPath = '/foo/bar@user-demo';
        $nodePath = '/foo/bar';
        $nodeTypeProperties = [
            'quux' => [
                'type' => 'array'
            ]
        ];
        $decodedPropertyValue = ['foo' => 'bar'];
        $source = [
            '__contextNodePath' => $contextPath,
            'quux' => json_encode($decodedPropertyValue)
        ];

        $mockNode = $this->setUpNodeWithNodeType($nodePath, $nodeTypeProperties);

        $mockNode->expects(self::once())->method('setProperty')->with('quux', $decodedPropertyValue);

        $this->nodeConverter->convertFrom($source, null, [], $this->mockConverterConfiguration);
    }

    /**
     * @param string $nodePath
     * @param array $nodeTypeProperties
     * @return NodeInterface
     */
    protected function setUpNodeWithNodeType($nodePath, $nodeTypeProperties = [])
    {
        $mockLiveWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $mockNode = $this->createMock(NodeInterface::class);
        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->expects(self::any())->method('getProperties')->will(self::returnValue($nodeTypeProperties));
        $mockNode->expects(self::any())->method('getNodeType')->will(self::returnValue($mockNodeType));

        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $mockContext->expects(self::any())->method('getWorkspace')->will(self::returnValue($mockLiveWorkspace));
        $mockContext->expects(self::any())->method('getNode')->with($nodePath)->will(self::returnValue($mockNode));

        $mockNode->expects(self::any())->method('getContext')->will(self::returnValue($mockContext));

        // Simulate context properties by returning the same properties that were given to the ContextFactory
        $this->mockContextFactory->expects(self::any())->method('create')->will(self::returnCallback(function ($contextProperties) use ($mockContext) {
            $mockContext->expects(self::any())->method('getProperties')->will(self::returnValue($contextProperties));
            return $mockContext;
        }));

        return $mockNode;
    }
}
