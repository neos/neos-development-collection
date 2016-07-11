<?php
namespace TYPO3\TypoScript\Tests\Unit\Core;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\TypoScript\Core\ExceptionHandlers\ThrowingHandler;

class RuntimeTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * if the rendering leads to an exception
     * the exception is transformed into 'content' by calling 'handleRenderingException'
     *
     * @test
     */
    public function renderHandlesExceptionDuringRendering()
    {
        $controllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
        $runtimeException = new \TYPO3\TypoScript\Exception\RuntimeException('I am a parent exception', 123, new \TYPO3\Flow\Exception('I am a previous exception'));
        $runtime = $this->getMockBuilder('TYPO3\TypoScript\Core\Runtime')->setMethods(array('evaluateInternal', 'handleRenderingException'))->setConstructorArgs(array(array(), $controllerContext))->getMock();
        $runtime->injectSettings(array('rendering' => array('exceptionHandler' => 'TYPO3\TypoScript\Core\ExceptionHandlers\ThrowingHandler')));
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
     * @expectedException \TYPO3\Flow\Exception
     * @test
     */
    public function handleRenderingExceptionThrowsException()
    {
        $objectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManager')->disableOriginalConstructor()->setMethods(array('isRegistered', 'get'))->getMock();
        $controllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
        $runtimeException = new \TYPO3\TypoScript\Exception\RuntimeException('I am a parent exception', 123, new \TYPO3\Flow\Exception('I am a previous exception'));
        $runtime =  new \TYPO3\TypoScript\Core\Runtime(array(), $controllerContext);
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
        $controllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();

        $eelEvaluator = $this->createMock('TYPO3\Eel\EelEvaluatorInterface');
        $runtime = $this->getAccessibleMock('TYPO3\TypoScript\Core\Runtime', array('dummy'), array(array(), $controllerContext));

        $this->inject($runtime, 'eelEvaluator', $eelEvaluator);


        $eelEvaluator->expects($this->once())->method('evaluate')->with('q(node).property("title")', $this->isInstanceOf('TYPO3\Eel\ProtectedContext'));

        $runtime->pushContextArray(array(
            'node' => 'Foo'
        ));

        $runtime->_call('evaluateEelExpression', 'q(node).property("title")');
    }

    /**
     * @test
     * @expectedException \TYPO3\TypoScript\Exception
     * @expectedExceptionCode 1395922119
     */
    public function evaluateWithCacheModeUncachedAndUnspecifiedContextThrowsException()
    {
        $mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
        $runtime = new \TYPO3\TypoScript\Core\Runtime(array(
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
     * @expectedException \TYPO3\Flow\Security\Exception
     */
    public function renderRethrowsSecurityExceptions()
    {
        $controllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
        $securityException = new \TYPO3\Flow\Security\Exception();
        $runtime = $this->getMockBuilder('TYPO3\TypoScript\Core\Runtime')->setMethods(array('evaluateInternal', 'handleRenderingException'))->setConstructorArgs(array(array(), $controllerContext))->getMock();
        $runtime->expects($this->any())->method('evaluateInternal')->will($this->throwException($securityException));

        $runtime->render('/foo/bar');
    }
}
