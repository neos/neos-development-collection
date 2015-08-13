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

use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
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

	public function setUp() {
		$this->nodeConverter = new NodeConverter();

		$this->mockNodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->getMock();
		$this->inject($this->nodeConverter, 'nodeDataRepository', $this->mockNodeDataRepository);

		$this->mockNodeFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Factory\NodeFactory')->disableOriginalConstructor()->getMock();
		$this->inject($this->nodeConverter, 'nodeFactory', $this->mockNodeFactory);

		$this->mockContextFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface')->disableOriginalConstructor()->getMock();
		$this->inject($this->nodeConverter, 'contextFactory', $this->mockContextFactory);

		$this->mockWorkspaceRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository')->setMethods(array('findOneByName'))->disableOriginalConstructor()->getMock();
		$this->inject($this->nodeConverter, 'workspaceRepository', $this->mockWorkspaceRepository);

		$this->mockConverterConfiguration = $this->getMockBuilder('TYPO3\Flow\Property\PropertyMappingConfigurationInterface')->disableOriginalConstructor()->getMock();
	}

	/**
	 * @test
	 */
	public function convertFromReturnsAnErrorWhenSourceIsAUuidButNoLiveWorkspaceCanBeFound() {
		$someUuid = 'b52c1d78-6c1d-48a9-a71c-8984eddf9dde';

		$this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue(NULL));

		$actualResult = $this->nodeConverter->convertFrom($someUuid);
		$this->assertInstanceOf('TYPO3\Flow\Error\Error', $actualResult);
	}

	/**
	 * @test
	 */
	public function convertFromReturnsAnErrorWhenSourceIsAUuidThatDoesNotBelongToAnExistingNodeDataRecord() {
		$someUuid = 'b52c1d78-6c1d-48a9-a71c-8984eddf9dde';

		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue($mockLiveWorkspace));

		$this->mockNodeDataRepository->expects($this->atLeastOnce())->method('findOneByIdentifier')->with($someUuid, $mockLiveWorkspace)->will($this->returnValue(NULL));

		$actualResult = $this->nodeConverter->convertFrom($someUuid);
		$this->assertInstanceOf('TYPO3\Flow\Error\Error', $actualResult);
	}

	/**
	 * @test
	 */
	public function convertFromReturnsMatchingNodeFromLiveWorkspaceWhenSourceIsAUuidThatBelongsToAnExistingNodeDataRecord() {
		$someUuid = 'b52c1d78-6c1d-48a9-a71c-8984eddf9dde';

		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue($mockLiveWorkspace));

		$mockNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
		$this->mockNodeDataRepository->expects($this->atLeastOnce())->method('findOneByIdentifier')->with($someUuid, $mockLiveWorkspace)->will($this->returnValue($mockNodeData));

		$mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\ContextInterface')->disableOriginalConstructor()->getMock();
		$this->mockContextFactory->expects($this->atLeastOnce())->method('create')->will($this->returnValue($mockContext));

		$mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Node')->disableOriginalConstructor()->getMock();
		$this->mockNodeFactory->expects($this->atLeastOnce())->method('createFromNodeData')->with($mockNodeData, $mockContext)->will($this->returnValue($mockNode));

		$actualResult = $this->nodeConverter->convertFrom($someUuid);
		$this->assertSame($mockNode, $actualResult);
	}

	/**
	 * @test
	 */
	public function convertFromPassesConverterConfigurationToCreateContextWhenSourceIsAUuid() {
		$someUuid = 'b52c1d78-6c1d-48a9-a71c-8984eddf9dde';
		$nodeConverter = $this->getAccessibleMock('TYPO3\TYPO3CR\TypeConverter\NodeConverter', array('createContext'));

		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue($mockLiveWorkspace));
		$this->inject($nodeConverter, 'workspaceRepository', $this->mockWorkspaceRepository);

		$this->inject($nodeConverter, 'nodeDataRepository', $this->mockNodeDataRepository);
		$this->inject($nodeConverter, 'nodeFactory', $this->mockNodeFactory);

		$mockNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();
		$this->mockNodeDataRepository->expects($this->atLeastOnce())->method('findOneByIdentifier')->with($someUuid, $mockLiveWorkspace)->will($this->returnValue($mockNodeData));

		$mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\ContextInterface')->disableOriginalConstructor()->getMock();
		$nodeConverter->expects($this->atLeastOnce())->method('createContext')->with('live', $this->mockConverterConfiguration)->will($this->returnValue($mockContext));

		$nodeConverter->convertFrom($someUuid, NULL, array(), $this->mockConverterConfiguration);
	}
}
