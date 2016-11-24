<?php
namespace TYPO3\Neos\Tests\Unit\TypoScript;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Exception;
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
class ConvertUrisImplementationTest extends UnitTestCase
{
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

    public function setUp()
    {
        $this->convertUrisImplementation = $this->getAccessibleMock(ConvertUrisImplementation::class, array('tsValue'), array(), '', false);

        $this->mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));

        $this->mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContext));

        $this->mockHttpUri = $this->getMockBuilder(Uri::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpUri->expects($this->any())->method('getHost')->will($this->returnValue('localhost'));

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($this->mockHttpUri));

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

        $this->mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->mockActionRequest));

        $this->mockLinkingService = $this->createMock(LinkingService::class);
        $this->convertUrisImplementation->_set('linkingService', $this->mockLinkingService);

        $this->mockTsRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $this->mockTsRuntime->expects($this->any())->method('getControllerContext')->will($this->returnValue($this->mockControllerContext));
        $this->convertUrisImplementation->_set('tsRuntime', $this->mockTsRuntime);
    }

    protected function addValueExpectation($value, $node = null, $forceConversion = false, $externalLinkTarget = null, $resourceLinkTarget = null, $absolute = false)
    {
        $this->convertUrisImplementation
            ->expects($this->atLeastOnce())
            ->method('tsValue')
            ->will($this->returnValueMap(array(
                array('value', $value),
                array('node', $node ?: $this->mockNode),
                array('forceConversion', $forceConversion),
                array('externalLinkTarget', $externalLinkTarget),
                array('resourceLinkTarget', $resourceLinkTarget),
                array('absolute', $absolute)
            )));
    }

    /**
     * @test
     * @expectedException \TYPO3\Neos\Domain\Exception
     */
    public function evaluateThrowsExceptionIfValueIsNoString()
    {
        $someObject = new \stdClass();
        $this->addValueExpectation($someObject);

        $this->convertUrisImplementation->evaluate();
    }

    /**
     * @test
     * @expectedException \TYPO3\Neos\Domain\Exception
     */
    public function evaluateThrowsExceptionIfTheCurrentContextArrayDoesNotContainANode()
    {
        $this->addValueExpectation('some string', new \stdClass());

        $this->convertUrisImplementation->evaluate();
    }

    /**
     * @test
     */
    public function evaluateDoesNotModifyTheValueIfItDoesNotContainNodeUris()
    {
        $value = ' this Is some string with line' . chr(10) . ' breaks, special chärß and leading/trailing space  ';
        $this->addValueExpectation($value);

        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

        $actualResult = $this->convertUrisImplementation->evaluate();
        $this->assertSame($value, $actualResult);
    }

    /**
     * @test
     */
    public function evaluateDoesNotModifyTheValueIfNotExecutedInLiveWorkspace()
    {
        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('not-live'));

        $value = 'This string contains a node URI: node://aeabe76a-551a-495f-a324-ad9a86b2aff7 and two <a href="node://cb2d0e4a-7d2f-4601-981a-f9a01530f53f">node</a> <a href="node://aeabe76a-551a-495f-a324-ad9a86b2aff7">links</a>.';
        $this->addValueExpectation($value);

        $actualResult = $this->convertUrisImplementation->evaluate();
        $this->assertSame($value, $actualResult);
    }

    /**
     * @test
     */
    public function evaluateDoesModifyTheValueIfExecutedInLiveWorkspaceWithTheForceConvertionOptionSet()
    {
        $nodeIdentifier1 = 'aeabe76a-551a-495f-a324-ad9a86b2aff7';
        $nodeIdentifier2 = 'cb2d0e4a-7d2f-4601-981a-f9a01530f53f';
        $value = 'This string contains a node URI: node://' . $nodeIdentifier1 . ' and two <a href="node://' . $nodeIdentifier2 . '">node</a> <a href="node://' . $nodeIdentifier1 . '">links</a>.';
        $this->addValueExpectation($value, null, true);

        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

        $self = $this;
        $this->mockLinkingService->expects($this->atLeastOnce())->method('resolveNodeUri')->will($this->returnCallback(function ($nodeUri) use ($self, $nodeIdentifier1, $nodeIdentifier2) {
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
    public function evaluateReplacesAllNodeUrisInTheGivenValue()
    {
        $nodeIdentifier1 = 'aeabe76a-551a-495f-a324-ad9a86b2aff7';
        $nodeIdentifier2 = 'cb2d0e4a-7d2f-4601-981a-f9a01530f53f';
        $value = 'This string contains a node URI: node://' . $nodeIdentifier1 . ' and two <a href="node://' . $nodeIdentifier2 . '">node</a> <a href="node://' . $nodeIdentifier1 . '">links</a>.';
        $this->addValueExpectation($value);

        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

        $self = $this;
        $this->mockLinkingService->expects($this->atLeastOnce())->method('resolveNodeUri')->will($this->returnCallback(function ($nodeUri) use ($self, $nodeIdentifier1, $nodeIdentifier2) {
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
    public function evaluateReplacesUnresolvableNodeUrisWithAnEmptyString()
    {
        $unknownNodeIdentifier = 'aeabe76a-551a-495f-a324-ad9a86b2aff7';
        $value = 'This string contains an unresolvable node URI: node://' . $unknownNodeIdentifier . ' and a <a href="node://' . $unknownNodeIdentifier . '">link</a>.';
        $this->addValueExpectation($value);

        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

        $expectedResult = 'This string contains an unresolvable node URI:  and a link.';
        $actualResult = $this->convertUrisImplementation->evaluate();
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * This test checks that targets for external links are correctly replaced
     *
     * @test
     */
    public function evaluateReplaceExternalLinkTargets()
    {
        $nodeIdentifier = 'aeabe76a-551a-495f-a324-ad9a86b2aff7';
        $externalLinkTarget = '_blank';

        $value = 'This string contains a link to a node: <a href="node://' . $nodeIdentifier . '">node</a> and one to an external url with a target set <a target="top" href="http://www.example.org">example</a> and one without a target <a href="http://www.example.org">example2</a>';
        $this->addValueExpectation($value, null, false, $externalLinkTarget, null);

        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

        $self = $this;
        $this->mockLinkingService->expects($this->atLeastOnce())->method('resolveNodeUri')->will($this->returnCallback(function ($nodeUri) use ($self, $nodeIdentifier) {
            if ($nodeUri === 'node://' . $nodeIdentifier) {
                return 'http://localhost/uri/01';
            } else {
                $self->fail('Unexpected node URI "' . $nodeUri . '"');
            }
        }));

        $expectedResult = 'This string contains a link to a node: <a href="http://localhost/uri/01">node</a> and one to an external url with a target set <a target="' . $externalLinkTarget . '" href="http://www.example.org">example</a> and one without a target <a target="' . $externalLinkTarget . '" href="http://www.example.org">example2</a>';
        $actualResult = $this->convertUrisImplementation->evaluate();
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * This test checks that targets for resource links are correctly replaced
     *
     * @test
     */
    public function evaluateReplaceResourceLinkTargets()
    {
        $assetIdentifier = 'aeabe76a-551a-495f-a324-ad9a86b2aff8';
        $resourceLinkTarget = '_blank';

        $value = 'This string contains two asset links and an external link: one with a target set <a target="top" href="asset://' . $assetIdentifier . '">example</a> and one without a target <a href="asset://' . $assetIdentifier . '">example2</a> and an external link <a href="http://www.example.org">example3</a>';
        $this->addValueExpectation($value, null, false, null, $resourceLinkTarget);

        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

        $self = $this;
        $this->mockLinkingService->expects($this->atLeastOnce())->method('resolveAssetUri')->will($this->returnCallback(function ($assetUri) use ($self, $assetIdentifier) {
            if ($assetUri !== 'asset://' . $assetIdentifier) {
                $self->fail('Unexpected asset URI "' . $assetUri . '"');
            }
            return 'http://localhost/_Resources/01';
        }));

        $expectedResult = 'This string contains two asset links and an external link: one with a target set <a target="' . $resourceLinkTarget . '" href="http://localhost/_Resources/01">example</a> and one without a target <a target="' . $resourceLinkTarget . '" href="http://localhost/_Resources/01">example2</a> and an external link <a href="http://www.example.org">example3</a>';
        $actualResult = $this->convertUrisImplementation->evaluate();
        $this->assertSame($expectedResult, $actualResult);
    }
}
