<?php
namespace Neos\Fusion\Tests\Unit\TypoScriptObjects\Http;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\ObjectAccess;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\TypoScriptObjects\Http\ResponseHeadImplementation;
use Neos\Fusion\TypoScriptObjects\TagImplementation;

/**
 * Testcase for the TypoScript ResponseHead object
 */
class ResponseHeadImplementationTest extends UnitTestCase
{
    /**
     * @var Runtime
     */
    protected $mockTsRuntime;

    public function setUp()
    {
        parent::setUp();
        $this->mockTsRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
    }

    public function responseHeadExamples()
    {
        return array(
            'default properties' => array(null, null, array(), "HTTP/1.1 200 OK\r\n\r\n"),
            'set header' => array(null, null, array('Content-Type' => 'application/json'), "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n"),
            'set status code' => array(null, 404, array(), "HTTP/1.1 404 Not Found\r\n\r\n")
        );
    }

    /**
     * @test
     * @dataProvider responseHeadExamples
     */
    public function evaluateTests($httpVersion, $statusCode, $headers, $expectedOutput)
    {
        $path = 'responseHead/test';

        $this->mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath) use ($path, $httpVersion, $statusCode, $headers) {
            $relativePath = str_replace($path . '/', '', $evaluatePath);
            switch ($relativePath) {
                case 'httpVersion':
                    return $httpVersion;
                case 'statusCode':
                    return $statusCode;
                case 'headers':
                    return $headers;
            }
            return isset($properties[$relativePath]) ? $properties[$relativePath] : null;
        }));

        $typoScriptObjectName = 'Neos.Fusion:Http.ResponseHead';
        $renderer = new ResponseHeadImplementation($this->mockTsRuntime, $path, $typoScriptObjectName);

        $result = $renderer->evaluate();
        $this->assertEquals($expectedOutput, $result);
    }
}
