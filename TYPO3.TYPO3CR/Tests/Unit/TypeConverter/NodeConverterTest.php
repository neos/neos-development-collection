<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use PHPUnit_Framework_Assert as Assert;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\TypeConverter\NodeConverter;

/**
 * Testcase for the Node TypeConverter
 */
class NodeConverterTest extends UnitTestCase {

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

	public function setUp() {
		$this->nodeConverter = new NodeConverter();

		$this->mockContextFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface')->disableOriginalConstructor()->getMock();
		$this->inject($this->nodeConverter, 'contextFactory', $this->mockContextFactory);

		$this->mockPropertyMapper = $this->getMock('TYPO3\Flow\Property\PropertyMapper');
		$this->inject($this->nodeConverter, 'propertyMapper', $this->mockPropertyMapper);

		$this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$this->inject($this->nodeConverter, 'objectManager', $this->mockObjectManager);

		$this->mockConverterConfiguration = $this->getMockBuilder('TYPO3\Flow\Property\PropertyMappingConfigurationInterface')->disableOriginalConstructor()->getMock();
	}

	/**
	 * @test
	 */
	public function convertFromSetsRemovedContentShownContextPropertyFromConfigurationForContextPathSource() {
		$contextPath = '/foo/bar@user-demo';
		$nodePath = '/foo/bar';

		$mockNode = $this->setUpNodeWithNodeType($nodePath);

		$this->mockConverterConfiguration->expects($this->any())->method('getConfigurationValue')->with('TYPO3\TYPO3CR\TypeConverter\NodeConverter', NodeConverter::REMOVED_CONTENT_SHOWN)->will($this->returnValue(TRUE));

		$result = $this->nodeConverter->convertFrom($contextPath, NULL, array(), $this->mockConverterConfiguration);
		$this->assertSame($mockNode, $result);

		$contextProperties = $mockNode->getContext()->getProperties();
		$this->assertArrayHasKey('removedContentShown', $contextProperties, 'removedContentShown context property should be set');
		$this->assertTrue($contextProperties['removedContentShown'], 'removedContentShown context property should be TRUE');
	}

	/**
	 * @test
	 */
	public function convertFromUsesPropertyMapperToConvertNodePropertyOfArrayType() {
		$contextPath = '/foo/bar@user-demo';
		$nodePath = '/foo/bar';
		$nodeTypeProperties = array(
			'assets' => array(
				'type' => 'array<TYPO3\Media\Domain\Model\Asset>'
			)
		);
		$decodedPropertyValue = array('8aaf4dd2-bd85-11e3-ae3d-14109fd7a2dd', '8febe94a-bd85-11e3-8401-14109fd7a2dd');
		$source = array(
			'__contextNodePath' => $contextPath,
			'assets' => json_encode($decodedPropertyValue)
		);

		$convertedPropertyValue = array(new \stdClass(), new \stdClass());

		$mockNode = $this->setUpNodeWithNodeType($nodePath, $nodeTypeProperties);

		$this->mockObjectManager->expects($this->any())->method('isRegistered')->with('TYPO3\Media\Domain\Model\Asset')->will($this->returnValue(TRUE));

		$this->mockPropertyMapper->expects($this->once())->method('convert')->with($decodedPropertyValue, $nodeTypeProperties['assets']['type'])->will($this->returnValue($convertedPropertyValue));
		$mockNode->expects($this->once())->method('setProperty')->with('assets', $convertedPropertyValue);

		$this->nodeConverter->convertFrom($source, NULL, array(), $this->mockConverterConfiguration);
	}

	/**
	 * @param string $nodePath
	 * @param array $nodeTypeProperties
	 * @return NodeInterface
	 */
	protected function setUpNodeWithNodeType($nodePath, $nodeTypeProperties = array()) {
		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();
		$mockNodeType->expects($this->any())->method('getProperties')->will($this->returnValue($nodeTypeProperties));
		$mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue($mockNodeType));

		$mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
		$mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockLiveWorkspace));
		$mockContext->expects($this->any())->method('getNode')->with($nodePath)->will($this->returnValue($mockNode));

		$mockNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));

		// Simulate context properties by returning the same properties that were given to the ContextFactory
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnCallback(function($contextProperties) use ($mockContext) {
			$mockContext->expects($this->any())->method('getProperties')->will($this->returnValue($contextProperties));
			return $mockContext;
		}));

		return $mockNode;
	}
}
