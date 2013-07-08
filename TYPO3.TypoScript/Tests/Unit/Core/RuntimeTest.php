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

class RuntimeTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function renderHandlesExceptionDuringRendering() {
		$controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$runtimeException = new \TYPO3\TypoScript\Exception\RuntimeException('I am a parent exception', 123, new \TYPO3\Flow\Exception('I am a previous exception'));
		$runtime = $this->getMock('TYPO3\TypoScript\Core\Runtime', array('evaluateInternal', 'handleRenderingException'), array(array(), $controllerContext));
		$runtime->injectSettings(array('handleRenderingExceptions' => 'throw'));
		$runtime->expects($this->any())->method('evaluateInternal')->will($this->throwException($runtimeException));
		$runtime->expects($this->once())->method('handleRenderingException')->with('/foo/bar', $runtimeException)->will($this->returnValue('Exception Message'));

		$output = $runtime->render('/foo/bar');

		$this->assertEquals('Exception Message', $output);
	}

	/**
	 * @expectedException \TYPO3\Flow\Exception
	 * @test
	 */
	public function handleRenderingExceptionThrowsException() {
		$controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$runtimeException = new \TYPO3\TypoScript\Exception\RuntimeException('I am a parent exception', 123, new \TYPO3\Flow\Exception('I am a previous exception'));
		$runtime =  new \TYPO3\TypoScript\Core\Runtime(array(), $controllerContext);
		$runtime->injectSettings(array('handleRenderingExceptions' => 'throw'));

		$runtime->handleRenderingException('/foo/bar', $runtimeException);
	}

	/**
	 * @test
	 */
	public function handleRenderingExceptionRendersHtmlMessage() {
		$controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$runtimeException = new \TYPO3\TypoScript\Exception\RuntimeException('I am a parent exception', 123, new \TYPO3\Flow\Exception('I am a previous exception'));
		$systemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
		$runtime =  new \TYPO3\TypoScript\Core\Runtime(array(), $controllerContext);
		$runtime->injectSettings(array('handleRenderingExceptions' => 'htmlMessage'));
		$this->inject($runtime, 'systemLogger', $systemLogger);

		$output = $runtime->handleRenderingException('/foo/bar', $runtimeException);

		$this->assertContains('neos-rendering-exception', $output);
	}

	/**
	 * @test
	 */
	public function handleRenderingExceptionRendersXmlComment() {
		$controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$runtimeException = new \TYPO3\TypoScript\Exception\RuntimeException('I am a parent exception', 123, new \TYPO3\Flow\Exception('I am a previous exception'));
		$systemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
		$runtime =  new \TYPO3\TypoScript\Core\Runtime(array(), $controllerContext);
		$runtime->injectSettings(array('handleRenderingExceptions' => 'xmlComment'));
		$this->inject($runtime, 'systemLogger', $systemLogger);

		$output = $runtime->handleRenderingException('/foo/bar', $runtimeException);

		$this->assertContains('<!-- Exception while', $output);
	}

	/**
	 * @test
	 */
	public function handleRenderingExceptionRendersPlainText() {
		$controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$runtimeException = new \TYPO3\TypoScript\Exception\RuntimeException('I am a parent exception', 123, new \TYPO3\Flow\Exception('I am a previous exception'));
		$systemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
		$runtime =  new \TYPO3\TypoScript\Core\Runtime(array(), $controllerContext);
		$runtime->injectSettings(array('handleRenderingExceptions' => 'plainText'));
		$this->inject($runtime, 'systemLogger', $systemLogger);

		$output = $runtime->handleRenderingException('/foo/bar', $runtimeException);

		$this->assertContains('Exception while rendering', $output);
	}

	/**
	 * @test
	 */
	public function handleRenderingExceptionSuppresses() {
		$controllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);
		$runtimeException = new \TYPO3\TypoScript\Exception\RuntimeException('I am a parent exception', 123, new \TYPO3\Flow\Exception('I am a previous exception'));
		$systemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
		$runtime =  new \TYPO3\TypoScript\Core\Runtime(array(), $controllerContext);
		$runtime->injectSettings(array('handleRenderingExceptions' => 'suppress'));
		$this->inject($runtime, 'systemLogger', $systemLogger);

		$output = $runtime->handleRenderingException('/foo/bar', $runtimeException);

		$this->assertEquals('', $output);
	}

}