<?php
namespace Neos\Fusion\Tests\Unit\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\EelEvaluatorInterface;
use Neos\Eel\ProtectedContext;
use Neos\Flow\Exception;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\ExceptionHandlers\ThrowingHandler;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception\RuntimeException;

class RuntimeTest extends UnitTestCase
{
    /**
     * if the rendering leads to an exception
     * the exception is transformed into 'content' by calling 'handleRenderingException'
     *
     * @test
     */
    public function renderHandlesExceptionDuringRendering()
    {
        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $runtimeException = new RuntimeException('I am a parent exception', 123, new Exception('I am a previous exception'));
        $runtime = $this->getMockBuilder(Runtime::class)->setMethods(array('evaluateInternal', 'handleRenderingException'))->setConstructorArgs(array(array(), $controllerContext))->getMock();
        $runtime->injectSettings(array('rendering' => array('exceptionHandler' => ThrowingHandler::class)));
        $runtime->expects($this->any())->method('evaluateInternal')->will($this->throwException($runtimeException));
        $runtime->expects($this->once())->method('handleRenderingException')->with('/foo/bar', $runtimeException)->will($this->returnValue('Exception Message'));

        $output = $runtime->render('/foo/bar');

        $this->assertEquals('Exception Message', $output);
    }

    /**
     * exceptions are rendered using the renderer from configuration
     *
     * if this handler throws exceptions, they are not handled
     *
     * @expectedException \Neos\Flow\Exception
     * @test
     */
    public function handleRenderingExceptionThrowsException()
    {
        $objectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->setMethods(array('isRegistered', 'get'))->getMock();
        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $runtimeException = new RuntimeException('I am a parent exception', 123, new Exception('I am a previous exception'));
        $runtime =  new Runtime(array(), $controllerContext);
        $this->inject($runtime, 'objectManager', $objectManager);
        $exceptionHandlerSetting = 'settings';
        $runtime->injectSettings(array('rendering' => array('exceptionHandler' => $exceptionHandlerSetting)));

        $objectManager->expects($this->once())->method('isRegistered')->with($exceptionHandlerSetting)->will($this->returnValue(true));
        $objectManager->expects($this->once())->method('get')->with($exceptionHandlerSetting)->will($this->returnValue(new ThrowingHandler()));

        $runtime->handleRenderingException('/foo/bar', $runtimeException);
    }

    /**
     * @test
     */
    public function evaluateProcessorForEelExpressionUsesProtectedContext()
    {
        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();

        $eelEvaluator = $this->createMock(EelEvaluatorInterface::class);
        $runtime = $this->getAccessibleMock(Runtime::class, array('dummy'), array(array(), $controllerContext));

        $this->inject($runtime, 'eelEvaluator', $eelEvaluator);


        $eelEvaluator->expects($this->once())->method('evaluate')->with('q(node).property("title")', $this->isInstanceOf(ProtectedContext::class));

        $runtime->pushContextArray(array(
            'node' => 'Foo'
        ));

        $runtime->_call('evaluateEelExpression', 'q(node).property("title")');
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Exception
     * @expectedExceptionCode 1395922119
     */
    public function evaluateWithCacheModeUncachedAndUnspecifiedContextThrowsException()
    {
        $mockControllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $runtime = new Runtime(array(
            'foo' => array(
                'bar' => array(
                    '__meta' => array(
                        'cache' => array(
                            'mode' => 'uncached'
                        )
                    )
                )
            )
        ), $mockControllerContext);

        $runtime->evaluate('foo/bar');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception
     */
    public function renderRethrowsSecurityExceptions()
    {
        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $securityException = new \Neos\Flow\Security\Exception();
        $runtime = $this->getMockBuilder(Runtime::class)->setMethods(array('evaluateInternal', 'handleRenderingException'))->setConstructorArgs(array(array(), $controllerContext))->getMock();
        $runtime->expects($this->any())->method('evaluateInternal')->will($this->throwException($securityException));

        $runtime->render('/foo/bar');
    }
}
