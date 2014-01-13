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

		$this->mockContextFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface')->disableOriginalConstructor()->getMock();
		$this->inject($this->nodeConverter, 'contextFactory', $this->mockContextFactory);

		$this->mockConverterConfiguration = $this->getMockBuilder('TYPO3\Flow\Property\PropertyMappingConfigurationInterface')->disableOriginalConstructor()->getMock();
	}

	/**
	 * @test
	 */
	public function convertFromReturnsAnErrorWhenSourceIsAUuidButNoLiveWorkspaceCanBeFound() {
		$someUuid = 'b52c1d78-6c1d-48a9-a71c-8984eddf9dde';

		$mockContext = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContextInterface');
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($mockContext));

		$actualResult = $this->nodeConverter->convertFrom($someUuid);

		$this->assertInstanceOf('TYPO3\Flow\Error\Error', $actualResult);
		$this->assertSame(1383577859, $actualResult->getCode(), 'Error code should match');
	}

	/**
	 * @test
	 */
	public function convertFromReturnsAnErrorWhenSourceIsAUuidThatDoesNotBelongToAnExistingNodeDataRecord() {
		$someUuid = 'b52c1d78-6c1d-48a9-a71c-8984eddf9dde';

		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

		$mockContext = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContextInterface');
		$mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockLiveWorkspace));
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($mockContext));

		$actualResult = $this->nodeConverter->convertFrom($someUuid);

		$this->assertInstanceOf('TYPO3\Flow\Error\Error', $actualResult);
		$this->assertSame(1382608594, $actualResult->getCode(), 'Error code should match');
	}

	/**
	 * @test
	 */
	public function convertFromReturnsMatchingNodeFromLiveWorkspaceWhenSourceIsAUuidThatBelongsToAnExistingNodeDataRecord() {
		$someUuid = 'b52c1d78-6c1d-48a9-a71c-8984eddf9dde';

		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$mockContext = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContextInterface');
		$mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockLiveWorkspace));
		$mockContext->expects($this->any())->method('getNodeByIdentifier')->with($someUuid)->will($this->returnValue($mockNode));
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($mockContext));

		$actualResult = $this->nodeConverter->convertFrom($someUuid);
		$this->assertSame($mockNode, $actualResult);
	}

	/**
	 * @test
	 */
	public function convertFromSetsRemovedContentShownContextPropertyFromConfigurationForContextPathSource() {
		$contextPath = '/foo/bar@user-demo';

		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

		$mockContext = $this->getMock('TYPO3\TYPO3CR\Domain\Service\ContextInterface');
		$mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockLiveWorkspace));
		$mockContext->expects($this->any())->method('getNodeByIdentifier')->with($contextPath)->will($this->returnValue($mockNode));

		$this->mockConverterConfiguration->expects($this->any())->method('getConfigurationValue')->with('TYPO3\TYPO3CR\TypeConverter\NodeConverter', NodeConverter::REMOVED_CONTENT_SHOWN)->will($this->returnValue(TRUE));

		$this->mockContextFactory->expects($this->any())->method('create')->with($this->callback(function($properties) {
			Assert::assertTrue(isset($properties['removedContentShown']), 'removedContentShown context property should be set');
			Assert::assertTrue($properties['removedContentShown'], 'removedContentShown context property should be TRUE');
			return TRUE;
		}))->will($this->returnValue($mockContext));


		$this->nodeConverter->convertFrom($contextPath, NULL, array(), $this->mockConverterConfiguration);
	}
}
