<?php
namespace TYPO3\TypoScript\Tests\Unit\TypoScriptObjects\Http;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TypoScript".      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\TypoScriptObjects\TagImplementation;

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
        $this->mockTsRuntime = $this->getMockBuilder('TYPO3\TypoScript\Core\Runtime')->disableOriginalConstructor()->getMock();
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

        $typoScriptObjectName = 'TYPO3.TypoScript:Http.ResponseHead';
        $renderer = new \TYPO3\TypoScript\TypoScriptObjects\Http\ResponseHeadImplementation($this->mockTsRuntime, $path, $typoScriptObjectName);

        $result = $renderer->evaluate();
        $this->assertEquals($expectedOutput, $result);
    }
}
