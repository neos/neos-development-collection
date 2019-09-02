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

use Neos\Flow\Http\Request;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Fusion\PluginImplementation;
use Neos\Fusion\Core\Runtime;
use Psr\Http\Message\ResponseInterface;

/**
 * Testcase for the ConvertNodeUris Fusion implementation
 */
class PluginImplementationTest extends UnitTestCase
{
    /**
     * @var PluginImplementation
     */
    protected $pluginImplementation;

    /**
     * @var Runtime
     */
    protected $mockRuntime;

    /**
     * @var ControllerContext
     */
    protected $mockControllerContext;


    public function setUp(): void
    {
        $this->pluginImplementation = $this->getAccessibleMock(PluginImplementation::class, ['buildPluginRequest'], [], '', false);

        $this->mockHttpUri = $this->getMockBuilder(Uri::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpUri->expects(self::any())->method('getHost')->will(self::returnValue('localhost'));

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects(self::any())->method('getUri')->will(self::returnValue($this->mockHttpUri));

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($this->mockHttpRequest));

        $this->mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->mockControllerContext->expects(self::any())->method('getRequest')->will(self::returnValue($this->mockActionRequest));

        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $this->mockRuntime->expects(self::any())->method('getControllerContext')->will(self::returnValue($this->mockControllerContext));
        $this->pluginImplementation->_set('runtime', $this->mockRuntime);

        $this->mockDispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $this->pluginImplementation->_set('dispatcher', $this->mockDispatcher);
    }

    /**
     * @return array
     */
    public function responseHeadersDataprovider()
    {
        return [
            [
                'Plugin response key does already exist in parent with same value',
                ['parent' => ['key' => 'value'], 'plugin' => ['key' => 'value']],
                ['key' => 'value']
            ],
            [
                'Plugin response key does not exist in parent with different value',
                ['parent' => ['key' => 'value'], 'plugin' => ['key' => 'otherValue']],
                ['key' => 'otherValue']
            ],
            [
                'Plugin response key does not exist in parent',
                ['parent' => ['key' => 'value'], 'plugin' => ['otherkey' => 'value']],
                ['key' => 'value', 'otherkey' => 'value']
            ]
        ];
    }


    /**
     * Test if the response headers of the plugin - set within the plugin action / dispatch - were set into the parent response.
     *
     * @dataProvider responseHeadersDataprovider
     * @test
     */
    public function evaluateSetHeaderIntoParent($message, $input, $expected)
    {
        $this->pluginImplementation->expects(self::any())->method('buildPluginRequest')->will(self::returnValue($this->mockActionRequest));

        $parentResponse = new \GuzzleHttp\Psr7\Response();
        $this->_setHeadersIntoResponse($parentResponse, $input['parent']);
        $this->mockControllerContext->expects(self::any())->method('getResponse')->will(self::returnValue($parentResponse));

        $this->mockDispatcher->expects(self::any())->method('dispatch')->will(self::returnCallback(function ($request, $response) use ($input) {
            $response = $this->_setHeadersIntoResponse($response, $input['plugin']);
        }));

        $this->pluginImplementation->evaluate();

        foreach ($expected as $expectedKey => $expectedValue) {
            self::assertEquals($expectedValue, (string)$parentResponse->getHeaders()->get($expectedKey), $message);
        }
    }

    /**
     *  Sets the array based headers into the Response
     *
     * @param ResponseInterface $response
     * @param $headers
     * @return ResponseInterface
     */
    private function _setHeadersIntoResponse(ResponseInterface $response, $headers): ResponseInterface
    {
        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }
}
