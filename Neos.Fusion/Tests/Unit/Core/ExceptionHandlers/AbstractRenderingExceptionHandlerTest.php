<?php
namespace Neos\Fusion\Tests\Unit\Core\ExceptionHandlers;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Exception;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Fusion\Fixtures\AbstractRenderingExceptionHandler;

require_once(__DIR__ . '/../../Fixtures/AbstractRenderingExceptionHandler.php');

/**
 * Test for the AbstractRenderingExceptionHandler
 */
class AbstractRenderingExceptionHandlerTest extends UnitTestCase
{
    /**
     * instance under test
     *
     * @var AbstractRenderingExceptionHandler
     */
    protected $handler;

    /**
     * Sets up this test case
     */
    public function setUp(): void
    {
        $this->handler = new AbstractRenderingExceptionHandler();
        $runtimeMock = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $this->handler->setRuntime($runtimeMock);
    }

    /**
     * exceptions are handled and transformed to a message
     *
     * @test
     */
    public function handleExceptions()
    {
        $exception = new \Exception();
        $actual = $this->handler->handleRenderingException('path', $exception);

        self::assertEquals($this->handler->getMessage(), $actual, 'incorrect message received');
        self::assertSame($exception, $this->handler->getException(), 'incorrect exception passed to stub');
        self::assertEquals(null, $this->handler->getReferenceCode(), 'incorrect reference code passed to stub');
        self::assertEquals('path', $this->handler->getFusionPath(), 'incorrect Fusion path passed to stub');
    }

    /**
     * exceptions are handled and transformed to a message
     *
     * @test
     */
    public function useReferenceCodes()
    {
        $exception = new Exception();
        $actual = $this->handler->handleRenderingException('path', $exception);

        self::assertEquals($this->handler->getMessage(), $actual, 'incorrect message received');
        self::assertSame($exception, $this->handler->getException(), 'incorrect exception passed to stub');
        self::assertEquals($exception->getReferenceCode(), $this->handler->getReferenceCode(), 'incorrect reference code passed to stub');
        self::assertEquals('path', $this->handler->getFusionPath(), 'incorrect Fusion path passed to stub');
    }

    /**
     * runtime exceptions are unpacked,
     * meaning that the inner fusion path an the inner exception is used to generate the message
     *
     * @test
     */
    public function unpackRuntimeException()
    {
        $exception = new Exception();
        $actual = $this->handler->handleRenderingException('path', new RuntimeException('', 23, $exception, 'path2'));

        self::assertEquals($this->handler->getMessage(), $actual, 'incorrect message received');
        self::assertSame($exception, $this->handler->getException(), 'incorrect exception passed to stub');
        self::assertEquals($exception->getReferenceCode(), $this->handler->getReferenceCode(), 'incorrect reference code passed to stub');
        self::assertEquals('path2', $this->handler->getFusionPath(), 'incorrect Fusion path passed to stub');
    }

    /**
     * StopActionException are rethrown
     *
     * @test
     */
    public function neverHandleStopActionException()
    {
        $this->expectException(StopActionException::class);
        $this->handler->handleRenderingException('path', new StopActionException());
    }


    /**
     * SecurityException are rethrown
     *
     * @test
     */
    public function neverHandleSecurityException()
    {
        $this->expectException(\Neos\Flow\Security\Exception::class);
        $this->handler->handleRenderingException('path', new \Neos\Flow\Security\Exception());
    }
}
