<?php
namespace TYPO3\TypoScript\Tests\Unit\Core;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TypoScript\Core\ExceptionHandlers\ThrowingHandler;

class RuntimeTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * if the rendering leads to an exception
	 * the exception is transformed into 'content' by calling 'handleRenderingException'
	 *
	 * @test
	 */
	public function renderHandlesExceptionDuringRendering() {
		$controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$runtimeException = new \TYPO3\TypoScript\Exception\RuntimeException('I am a parent exception', 123, new \TYPO3\Flow\Exception('I am a previous exception'));
		$runtime = $this->getMock('TYPO3\TypoScript\Core\Runtime', array('evaluateInternal', 'handleRenderingException'), array(array(), $controllerContext));
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
	public function handleRenderingExceptionThrowsException() {
		$objectManager = $this->getMock('TYPO3\Flow\Object\ObjectManager', array('isRegistered', 'get'), array(), '', FALSE);
		$controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$runtimeException = new \TYPO3\TypoScript\Exception\RuntimeException('I am a parent exception', 123, new \TYPO3\Flow\Exception('I am a previous exception'));
		$runtime =  new \TYPO3\TypoScript\Core\Runtime(array(), $controllerContext);
		$this->inject($runtime, 'objectManager', $objectManager);
		$exceptionHandlerSetting = 'settings';
		$runtime->injectSettings(array('rendering' => array('exceptionHandler' => $exceptionHandlerSetting)));

		$objectManager->expects($this->once())->method('isRegistered')->with($exceptionHandlerSetting)->will($this->returnValue(TRUE));
		$objectManager->expects($this->once())->method('get')->with($exceptionHandlerSetting)->will($this->returnValue(new ThrowingHandler()));

		$runtime->handleRenderingException('/foo/bar', $runtimeException);
	}

	/**
	 * @test
	 */
	public function evaluateProcessorForEelExpressionUsesProtectedContext() {
		$controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);

		$eelEvaluator = $this->getMock('TYPO3\Eel\EelEvaluatorInterface');
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
	public function evaluateWithCacheModeUncachedAndUnspecifiedContextThrowsException() {
		$mockControllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
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
	public function renderRethrowsSecurityExceptions() {
		$controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$securityException = new \TYPO3\Flow\Security\Exception();
		$runtime = $this->getMock('TYPO3\TypoScript\Core\Runtime', array('evaluateInternal', 'handleRenderingException'), array(array(), $controllerContext));
		$runtime->expects($this->any())->method('evaluateInternal')->will($this->throwException($securityException));

		$runtime->render('/foo/bar');
	}

}
