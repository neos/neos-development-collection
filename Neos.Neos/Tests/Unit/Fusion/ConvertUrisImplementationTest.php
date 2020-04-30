<?php
namespace Neos\Neos\Tests\Unit\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Service\LinkingService;
use Neos\Neos\Fusion\ConvertUrisImplementation;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Fusion\Core\Runtime;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Testcase for the ConvertNodeUris Fusion implementation
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
    protected $mockRuntime;

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

    public function setUp(): void
    {
        $this->convertUrisImplementation = $this->getAccessibleMock(ConvertUrisImplementation::class, ['fusionValue'], [], '', false);

        $this->mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();

        $this->mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->mockContext->expects(self::any())->method('getWorkspace')->will(self::returnValue($this->mockWorkspace));

        $this->mockNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $this->mockNode->expects(self::any())->method('getContext')->will(self::returnValue($this->mockContext));

        $this->mockHttpUri = $this->getMockBuilder(Uri::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpUri->expects(self::any())->method('getHost')->will(self::returnValue('localhost'));

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects(self::any())->method('getUri')->willReturn($this->mockHttpUri);

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($this->mockHttpRequest));

        $this->mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->mockControllerContext->expects(self::any())->method('getRequest')->will(self::returnValue($this->mockActionRequest));

        $this->mockLinkingService = $this->createMock(LinkingService::class);
        $this->convertUrisImplementation->_set('linkingService', $this->mockLinkingService);

        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $this->mockRuntime->expects(self::any())->method('getControllerContext')->will(self::returnValue($this->mockControllerContext));
        $this->convertUrisImplementation->_set('runtime', $this->mockRuntime);
    }

    protected function addValueExpectation($value, $node = null, $forceConversion = false, $externalLinkTarget = null, $resourceLinkTarget = null, $absolute = false, $setNoOpener = true)
    {
        $this->convertUrisImplementation
            ->expects(self::atLeastOnce())
            ->method('fusionValue')
            ->will($this->returnValueMap([
                ['value', $value],
                ['node', $node ?: $this->mockNode],
                ['forceConversion', $forceConversion],
                ['externalLinkTarget', $externalLinkTarget],
                ['resourceLinkTarget', $resourceLinkTarget],
                ['absolute', $absolute],
                ['setNoOpener', $setNoOpener]
            ]));
    }

    /**
     * @test
     */
    public function evaluateThrowsExceptionIfValueIsNoString()
    {
        $this->expectException(Exception::class);
        $someObject = new \stdClass();
        $this->addValueExpectation($someObject);

        $this->convertUrisImplementation->evaluate();
    }

    /**
     * @test
     */
    public function evaluateThrowsExceptionIfTheCurrentContextArrayDoesNotContainANode()
    {
        $this->expectException(Exception::class);
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

        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));

        $actualResult = $this->convertUrisImplementation->evaluate();
        self::assertSame($value, $actualResult);
    }

    /**
     * @test
     */
    public function evaluateDoesNotModifyTheValueIfNotExecutedInLiveWorkspace()
    {
        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('not-live'));

        $value = 'This string contains a node URI: node://aeabe76a-551a-495f-a324-ad9a86b2aff7 and two <a href="node://cb2d0e4a-7d2f-4601-981a-f9a01530f53f">node</a> <a href="node://aeabe76a-551a-495f-a324-ad9a86b2aff7">links</a>.';
        $this->addValueExpectation($value);

        $actualResult = $this->convertUrisImplementation->evaluate();
        self::assertSame($value, $actualResult);
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

        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));

        $self = $this;
        $this->mockLinkingService->expects(self::atLeastOnce())->method('resolveNodeUri')->will(self::returnCallback(function ($nodeUri) use ($self, $nodeIdentifier1, $nodeIdentifier2) {
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
        self::assertSame($expectedResult, $actualResult);
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

        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));

        $self = $this;
        $this->mockLinkingService->expects(self::atLeastOnce())->method('resolveNodeUri')->will(self::returnCallback(function ($nodeUri) use ($self, $nodeIdentifier1, $nodeIdentifier2) {
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
        self::assertSame($expectedResult, $actualResult);
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

        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));

        $expectedResult = 'This string contains an unresolvable node URI:  and a link.';
        $actualResult = $this->convertUrisImplementation->evaluate();
        self::assertSame($expectedResult, $actualResult);
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

        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));

        $self = $this;
        $this->mockLinkingService->expects(self::atLeastOnce())->method('resolveNodeUri')->will(self::returnCallback(function ($nodeUri) use ($self, $nodeIdentifier) {
            if ($nodeUri === 'node://' . $nodeIdentifier) {
                return 'http://localhost/uri/01';
            } else {
                $self->fail('Unexpected node URI "' . $nodeUri . '"');
            }
        }));

        $expectedResult = 'This string contains a link to a node: <a href="http://localhost/uri/01">node</a> and one to an external url with a target set <a target="' . $externalLinkTarget . '" rel="noopener" href="http://www.example.org">example</a> and one without a target <a target="' . $externalLinkTarget . '" rel="noopener" href="http://www.example.org">example2</a>';
        $actualResult = $this->convertUrisImplementation->evaluate();
        self::assertSame($expectedResult, $actualResult);
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

        $this->mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue('live'));

        $self = $this;
        $this->mockLinkingService->expects(self::atLeastOnce())->method('resolveAssetUri')->will(self::returnCallback(function ($assetUri) use ($self, $assetIdentifier) {
            if ($assetUri !== 'asset://' . $assetIdentifier) {
                $self->fail('Unexpected asset URI "' . $assetUri . '"');
            }
            return 'http://localhost/_Resources/01';
        }));

        $expectedResult = 'This string contains two asset links and an external link: one with a target set <a target="' . $resourceLinkTarget . '" rel="noopener" href="http://localhost/_Resources/01">example</a> and one without a target <a target="' . $resourceLinkTarget . '" rel="noopener" href="http://localhost/_Resources/01">example2</a> and an external link <a href="http://www.example.org">example3</a>';
        $actualResult = $this->convertUrisImplementation->evaluate();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function disablingSetNoOpenerWorks()
    {
        $value = 'This string contains an external link: <a href="http://www.example.org">example3</a>';
        $this->addValueExpectation($value, null, false, '_blank', null, false, false);
        $expectedResult = 'This string contains an external link: <a href="http://www.example.org">example3</a>';
        $actualResult = $this->convertUrisImplementation->evaluate();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * This test checks that targets for resource links are correctly replaced if the a Tag is inside a tag with the name beginning wit a
     *
     * @test
     */
    public function evaluateReplaceResourceLinkTargetsInsideTag()
    {
        $assetIdentifier = 'aeabe76a-551a-495f-a324-ad9a86b2aff8';
        $resourceLinkTarget = '_blank';

        $value = 'and an external link inside another tag beginning with a <article> test <a href="asset://' . $assetIdentifier . '">example1</a></article>';
        $this->addValueExpectation($value, null, false, null, $resourceLinkTarget);

        $this->mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));

        $self = $this;
        $this->mockLinkingService->expects($this->atLeastOnce())->method('resolveAssetUri')->will($this->returnCallback(function ($assetUri) use ($self, $assetIdentifier) {
            if ($assetUri !== 'asset://' . $assetIdentifier) {
                $self->fail('Unexpected asset URI "' . $assetUri . '"');
            }
            return 'http://localhost/_Resources/01';
        }));

        $expectedResult = 'and an external link inside another tag beginning with a <article> test <a target="' . $resourceLinkTarget . '" href="http://localhost/_Resources/01">example1</a></article>';
        $actualResult = $this->convertUrisImplementation->evaluate();
        $this->assertSame($expectedResult, $actualResult);
    }
}
