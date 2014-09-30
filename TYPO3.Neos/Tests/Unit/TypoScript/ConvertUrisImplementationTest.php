<?php
namespace TYPO3\Neos\Tests\Unit\TypoScript;

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
use TYPO3\Neos\Service\LinkingService;
use TYPO3\Neos\TypoScript\ConvertUrisImplementation;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TypoScript\Core\Runtime;

/**
 * Testcase for the ConvertNodeUris TypoScript implementation
 */
class ConvertUrisImplementationTest extends UnitTestCase {

	/**
	 * @var ConvertUrisImplementation
	 */
	protected $convertUrisImplementation;

	/**
	 * @var LinkingService
	 */
	protected $mockLinkingService;

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
	 * @var ControllerContext
	 */
	protected $mockControllerContext;

	/**
	 * @var UriBuilder
	 */
	protected $mockUriBuilder;

	public function setUp() {
		$this->convertUrisImplementation = $this->getAccessibleMock('TYPO3\Neos\TypoScript\ConvertUrisImplementation', array('tsValue'), array(), '', FALSE);

		$this->mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

		$this->mockContext = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\Context')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

		$this->mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();
		$this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContext));

		$this->mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();

		$this->mockLinkingService = $this->getMock('TYPO3\Neos\Service\LinkingService');
		$this->convertUrisImplementation->_set('linkingService', $this->mockLinkingService);

		$this->mockTsRuntime = $this->getMockBuilder('TYPO3\TypoScript\Core\Runtime')->disableOriginalConstructor()->getMock();
		$this->mockTsRuntime->expects($this->any())->method('getControllerContext')->will($this->returnValue($this->mockControllerContext));
		$this->convertUrisImplementation->_set('tsRuntime', $this->mockTsRuntime);
	}

	protected function addValueExpectation($value, $node = NULL, $forceConversion = FALSE) {
		$this->convertUrisImplementation
			->expects($this->atLeastOnce())
			->method('tsValue')
			->will($this->returnValueMap(array(
				array('value', $value),
				array('node', $node ?: $this->mockNode),
				array('forceConversion', $forceConversion)
			)));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Neos\Domain\Exception
	 */
	public function evaluateThrowsExceptionIfValueIsNoString() {
		$someObject = new \stdClass();
		$this->addValueExpectation($someObject);

		$this->convertUrisImplementation->evaluate();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Neos\Domain\Exception
	 */
	public function evaluateThrowsExceptionIfTheCurrentContextArrayDoesNotContainANode() {
		$this->addValueExpectation('some string', new \stdClass());

		$this->convertUrisImplementation->evaluate();
	}

	/**
	 * @test
	 */
	public function evaluateDoesNotModifyTheValueIfItDoesNotContainNodeUris() {
		$value = ' this Is some string with line' . chr(10) . ' breaks, special chärß and leading/trailing space  ';
		$this->addValueExpectation($value);

		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		$actualResult = $this->convertUrisImplementation->evaluate();
		$this->assertSame($value, $actualResult);
	}

	/**
	 * @test
	 */
	public function evaluateDoesNotModifyTheValueIfNotExecutedInLiveWorkspace() {
		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('not-live'));

		$value = 'This string contains a node URI: node://aeabe76a-551a-495f-a324-ad9a86b2aff7 and two <a href="node://cb2d0e4a-7d2f-4601-981a-f9a01530f53f">node</a> <a href="node://aeabe76a-551a-495f-a324-ad9a86b2aff7">links</a>.';
		$this->addValueExpectation($value);

		$actualResult = $this->convertUrisImplementation->evaluate();
		$this->assertSame($value, $actualResult);
	}

	/**
	 * @test
	 */
	public function evaluateDoesModifyTheValueIfExecutedInLiveWorkspaceWithTheForceConvertionOptionSet() {
		$nodeIdentifier1 = 'aeabe76a-551a-495f-a324-ad9a86b2aff7';
		$nodeIdentifier2 = 'cb2d0e4a-7d2f-4601-981a-f9a01530f53f';
		$value = 'This string contains a node URI: node://' . $nodeIdentifier1 . ' and two <a href="node://' . $nodeIdentifier2 . '">node</a> <a href="node://' . $nodeIdentifier1 . '">links</a>.';
		$this->addValueExpectation($value, NULL, TRUE);

		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		$self = $this;
		$this->mockLinkingService->expects($this->atLeastOnce())->method('resolveNodeUri')->will($this->returnCallback(function($nodeUri) use ($self, $nodeIdentifier1, $nodeIdentifier2) {
			if ($nodeUri === 'node://' . $nodeIdentifier1) {
				return 'http://replaced/uri/01';
			} elseif ($nodeUri === 'node://' . $nodeIdentifier2) {
				return 'http://replaced/uri/02';
			} else {
				$self->fail('Unexpected node URI "' . $nodeUri . '"');
			}
		}));

		$expectedResult = 'This string contains a node URI: http://replaced/uri/01 and two <a href="http://replaced/uri/02">node</a> <a href="http://replaced/uri/01">links</a>.';
		$actualResult = $this->convertUrisImplementation->evaluate();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function evaluateReplacesAllNodeUrisInTheGivenValue() {
		$nodeIdentifier1 = 'aeabe76a-551a-495f-a324-ad9a86b2aff7';
		$nodeIdentifier2 = 'cb2d0e4a-7d2f-4601-981a-f9a01530f53f';
		$value = 'This string contains a node URI: node://' . $nodeIdentifier1 . ' and two <a href="node://' . $nodeIdentifier2 . '">node</a> <a href="node://' . $nodeIdentifier1 . '">links</a>.';
		$this->addValueExpectation($value);

		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		$self = $this;
		$this->mockLinkingService->expects($this->atLeastOnce())->method('resolveNodeUri')->will($this->returnCallback(function($nodeUri) use ($self, $nodeIdentifier1, $nodeIdentifier2) {
			if ($nodeUri === 'node://' . $nodeIdentifier1) {
				return 'http://replaced/uri/01';
			} elseif ($nodeUri === 'node://' . $nodeIdentifier2) {
				return 'http://replaced/uri/02';
			} else {
				$self->fail('Unexpected node URI "' . $nodeUri . '"');
			}
		}));

		$expectedResult = 'This string contains a node URI: http://replaced/uri/01 and two <a href="http://replaced/uri/02">node</a> <a href="http://replaced/uri/01">links</a>.';
		$actualResult = $this->convertUrisImplementation->evaluate();
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
		$this->addValueExpectation($value);

		$this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

		$expectedResult = 'This string contains an unresolvable node URI:  and a <a href="">link</a>.';
		$actualResult = $this->convertUrisImplementation->evaluate();
		$this->assertSame($expectedResult, $actualResult);
	}

}
