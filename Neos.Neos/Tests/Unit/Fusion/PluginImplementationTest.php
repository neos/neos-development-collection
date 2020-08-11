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
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Fusion\PluginImplementation;
use Neos\Fusion\Core\Runtime;

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


    public function setUp()
    {
        $this->pluginImplementation = $this->getAccessibleMock(PluginImplementation::class, ['buildPluginRequest'], [], '', false);

        $this->mockHttpUri = $this->getMockBuilder(Uri::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpUri->expects($this->any())->method('getHost')->will($this->returnValue('localhost'));

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($this->mockHttpUri));

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

        $this->mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->mockActionRequest));

        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $this->mockRuntime->expects($this->any())->method('getControllerContext')->will($this->returnValue($this->mockControllerContext));
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
        $this->pluginImplementation->expects($this->any())->method('buildPluginRequest')->will($this->returnValue($this->mockActionRequest));

        $parentResponse = new Response();
        $this->_setHeadersIntoResponse($parentResponse, $input['parent']);
        $this->mockControllerContext->expects($this->any())->method('getResponse')->will($this->returnValue($parentResponse));

        $this->mockDispatcher->expects($this->any())->method('dispatch')->will($this->returnCallback(function ($request, $response) use ($input) {
            $this->_setHeadersIntoResponse($response, $input['plugin']);
        }));

        $this->mockRuntime->expects($this->any())->method('getCurrentContext')->willReturn(['node' => null, 'documentNode' => null]);

        $this->pluginImplementation->evaluate();

        foreach ($expected as $expectedKey => $expectedValue) {
            $this->assertEquals($expectedValue, (string)$parentResponse->getHeaders()->get($expectedKey), $message);
        }
    }

    /**
     *  Sets the array based headers into the Response
     *
     * @param Response $response
     * @param $headers
     */
    private function _setHeadersIntoResponse(Response $response, $headers)
    {
        foreach ($headers as $key => $value) {
            $response->getHeaders()->set($key, $value);
        }
    }
}
