<?php
namespace TYPO3\Neos\Tests\Unit\TypoScript\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\TypoScript\ConvertNodeUrisImplementation;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TypoScript\Core\Runtime;

/**
 * Testcase for the ConvertNodeUris TypoScript implementation
 */
class ConvertNodeUrisImplementationTest extends UnitTestCase {

	/**
	 * @var ConvertNodeUrisImplementation
	 */
	protected $convertNodeUrisImplementation;

	/**
	 * @var Runtime
	 */
	protected $mockTsRuntime;

	/**
	 * @var NodeDataRepository
	 */
	protected $mockNodeDataRepository;

	/**
	 * @var Context
	 */
	protected $mockContext;

	/**
	 * @var NodeInterface
	 */
	protected $mockNode;

	/**
	 * @var Workspace
	 */
	protected $mockWorkspace;

	/**
	 * @var NodeFactory
	 */
	protected $mockNodeFactory;

	/**
	 * @var ControllerContext
	 */
	protected $mockControllerContext;

	/**
	 * @var UriBuilder
	 */
	protected $mockUriBuilder;


	public function setUp() {
		$this->convertNodeUrisImplementation = $this->getAccessibleMock('TYPO3\Neos\TypoScript\ConvertNodeUrisImplementation', array('getValue'), array(), '', FALSE);

		$this->mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

		$this->mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$this->mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();
		$this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContext));

		$this->mockUriBuilder = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\UriBuilder')->disableOriginalConstructor()->getMock();

		$this->mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
		$this->mockControllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->mockUriBuilder));

		$this->mockTsRuntime = $this->getMockBuilder('TYPO3\TypoScript\Core\Runtime')->disableOriginalConstructor()->getMock();
		$this->mockTsRuntime->expects($this->any())->method('getCurrentContext')->will($this->returnValue(array('node' => $this->mockNode)));
		$this->mockTsRuntime->expects($this->any())->method('getControllerContext')->will($this->returnValue($this->mockControllerContext));
		$this->convertNodeUrisImplementation->_set('tsRuntime', $this->mockTsRuntime);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Neos\Domain\Exception
	 */
	public function evaluateThrowsExceptionIfValueIsNoString() {
		$someObject = new \stdClass();
		$this->convertNodeUrisImplementation->expects($this->atLeastOnce())->method('getValue')->will($this->returnValue($someObject));

		$this->convertNodeUrisImplementation->evaluate();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Neos\Domain\Exception
	 */
	public function evaluateThrowsExceptionIfTheCurrentContextArrayDoesNotContainANode() {
		$this->convertNodeUrisImplementation->expects($this->atLeastOnce())->method('getValue')->will($this->returnValue('some string'));

		$contextData = array(
			'node' => new \stdClass()
		);
		$mockTsRuntime = $this->getMockBuilder('TYPO3\TypoScript\Core\Runtime')->disableOriginalConstructor()->getMock();
		$mockTsRuntime->expects($this->atLeastOnce())->method('getCurrentContext')->will($this->returnValue($contextData));
		$this->convertNodeUrisImplementation->_set('tsRuntime', $mockTsRuntime);

		$this->convertNodeUrisImplementation->evaluate();
	}

	/**
	 * @test
	 */
	public function evaluateDoesNotModifyTheValueIfItDoesNotContainNodeUris() {
		$value = ' this Is some string with line' . chr(10) . ' breaks, special chärß and leading/trailing space  ';
		$this->convertNodeUrisImplementation->expects($this->atLeastOnce())->method('getValue')->will($this->returnValue($value));

		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		$actualResult = $this->convertNodeUrisImplementation->evaluate();
		$this->assertSame($value, $actualResult);
	}

	/**
	 * @test
	 */
	public function evaluateDoesNotModifyTheValueIfNotExecutedInLiveWorkspace() {
		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('not-live'));

		$value = 'This string contains a node URI: node://aeabe76a-551a-495f-a324-ad9a86b2aff7 and two <a href="node://cb2d0e4a-7d2f-4601-981a-f9a01530f53f">node</a> <a href="node://aeabe76a-551a-495f-a324-ad9a86b2aff7">links</a>.';
		$this->convertNodeUrisImplementation->expects($this->atLeastOnce())->method('getValue')->will($this->returnValue($value));

		$actualResult = $this->convertNodeUrisImplementation->evaluate();
		$this->assertSame($value, $actualResult);
	}

	/**
	 * @test
	 */
	public function evaluateReplacesAllNodeUrisInTheGivenValue() {
		$nodeIdentifier1 = 'aeabe76a-551a-495f-a324-ad9a86b2aff7';
		$nodeIdentifier2 = 'cb2d0e4a-7d2f-4601-981a-f9a01530f53f';
		$value = 'This string contains a node URI: node://' . $nodeIdentifier1 . ' and two <a href="node://' . $nodeIdentifier2 . '">node</a> <a href="node://' . $nodeIdentifier1 .'">links</a>.';
		$this->convertNodeUrisImplementation->expects($this->atLeastOnce())->method('getValue')->will($this->returnValue($value));

		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		$mockTargetNode1 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();
		$mockTargetNode2 = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

		$self = $this;

		$this->mockContext->expects($this->atLeastOnce())->method('getNodeByIdentifier')->will($this->returnCallback(function($nodeIdentifier) use ($self, $nodeIdentifier1, $nodeIdentifier2, $mockTargetNode1, $mockTargetNode2) {
			if ($nodeIdentifier === $nodeIdentifier1) {
				return $mockTargetNode1;
			} elseif ($nodeIdentifier === $nodeIdentifier2) {
				return $mockTargetNode2;
			} else {
				$self->fail('Unexpected node identifier "' . $nodeIdentifier . '"');
			}
		}));

		$this->mockUriBuilder->expects($this->atLeastOnce())->method('setFormat')->with('html')->will($this->returnValue($this->mockUriBuilder));
		$this->mockUriBuilder->expects($this->atLeastOnce())->method('uriFor')->will($this->returnCallback(function($action, $arguments, $controller, $package) use ($self, $mockTargetNode1, $mockTargetNode2) {
			$self->assertSame('show', $action);
			$self->assertSame('Frontend\\Node', $controller);
			$self->assertSame('TYPO3.Neos', $package);
			$self->assertInstanceOf('TYPO3\TYPO3CR\Domain\Model\NodeInterface', isset($arguments['node']) ? $arguments['node'] : NULL);
			$node = $arguments['node'];
			if ($node === $mockTargetNode1) {
				return 'http://replaced/uri/01';
			} elseif ($node === $mockTargetNode2) {
				return 'http://replaced/uri/02';
			} else {
				$self->fail('Unexpected node argument');
			}
		}));

		$expectedResult = 'This string contains a node URI: http://replaced/uri/01 and two <a href="http://replaced/uri/02">node</a> <a href="http://replaced/uri/01">links</a>.';
		$actualResult = $this->convertNodeUrisImplementation->evaluate();
		$this->assertSame($expectedResult, $actualResult);
	}


	/**
	 * This only verifies the current behavior that might be changed in the future (e.g. we could remove unresolved links instead of creating empty href attributes)
	 *
	 * @test
	 */
	public function evaluateReplacesUnresolvableNodeUrisWithAnEmptyString() {
		$unknownNodeIdentifier = 'aeabe76a-551a-495f-a324-ad9a86b2aff7';
		$value = 'This string contains an unresolvable node URI: node://' . $unknownNodeIdentifier . ' and a <a href="node://' . $unknownNodeIdentifier . '">link</a>.';
		$this->convertNodeUrisImplementation->expects($this->atLeastOnce())->method('getValue')->will($this->returnValue($value));

		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		$expectedResult = 'This string contains an unresolvable node URI:  and a <a href="">link</a>.';
		$actualResult = $this->convertNodeUrisImplementation->evaluate();
		$this->assertSame($expectedResult, $actualResult);
	}

}
