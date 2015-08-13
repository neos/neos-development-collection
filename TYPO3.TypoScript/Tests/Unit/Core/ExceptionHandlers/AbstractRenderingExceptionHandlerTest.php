<?php
namespace TYPO3\TypoScript\Tests\Unit\Core\ExceptionHandlers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TypoScript".      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TypoScript\Exception\RuntimeException;
use TYPO3\TypoScript\Fixtures\AbstractRenderingExceptionHandler;

require_once (__DIR__ . '/../../Fixtures/AbstractRenderingExceptionHandler.php');

/**
 * Test for the AbstractRenderingExceptionHandler
 */
class AbstractRenderingExceptionHandlerTest extends UnitTestCase {

	/**
	 * instance under test
	 *
	 * @var AbstractRenderingExceptionHandler
	 */
	protected $handler;

	/**
	 * Sets up this test case
	 */
	protected function setUp() {
		$this->handler = new AbstractRenderingExceptionHandler();
	}

	/**
	 * exceptions are handled and transformed to a message
	 *
	 * @test
	 */
	public function handleExceptions() {
		$exception = new \Exception();
		$actual = $this->handler->handleRenderingException('path', $exception);

		$this->assertEquals($this->handler->getMessage(), $actual, 'incorrect message received');
		$this->assertSame($exception, $this->handler->getException(), 'incorrect exception passed to stub');
		$this->assertEquals(NULL, $this->handler->getReferenceCode(), 'incorrect reference code passed to stub');
		$this->assertEquals('path', $this->handler->getTypoScriptPath(), 'incorrect typo script path passed to stub');
	}

	/**
	 * exceptions are handled and transformed to a message
	 *
	 * @test
	 */
	public function useReferenceCodes() {
		$exception = new Exception();
		$actual = $this->handler->handleRenderingException('path', $exception);

		$this->assertEquals($this->handler->getMessage(), $actual, 'incorrect message received');
		$this->assertSame($exception, $this->handler->getException(), 'incorrect exception passed to stub');
		$this->assertEquals($exception->getReferenceCode(), $this->handler->getReferenceCode(), 'incorrect reference code passed to stub');
		$this->assertEquals('path', $this->handler->getTypoScriptPath(), 'incorrect typo script path passed to stub');
	}

	/**
	 * runtime exceptions are unpacked,
	 * meaning that the inner typoscript path an the inner exception is used to generate the message
	 *
	 * @test
	 */
	public function unpackRuntimeException() {
		$exception = new Exception();
		$actual = $this->handler->handleRenderingException('path', new RuntimeException('', 23, $exception, 'path2'));

		$this->assertEquals($this->handler->getMessage(), $actual, 'incorrect message received');
		$this->assertSame($exception, $this->handler->getException(), 'incorrect exception passed to stub');
		$this->assertEquals($exception->getReferenceCode(), $this->handler->getReferenceCode(), 'incorrect reference code passed to stub');
		$this->assertEquals('path2', $this->handler->getTypoScriptPath(), 'incorrect typo script path passed to stub');
	}

	/**
	 * StopActionException are rethrown
	 *
	 * @expectedException TYPO3\Flow\Mvc\Exception\StopActionException
	 * @test
	 */
	public function neverHandleStopActionException() {
		$this->handler->handleRenderingException('path', new StopActionException());
	}
}

?>