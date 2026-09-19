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
use GuzzleHttp\Psr7\Message;
use Neos\Eel\EelEvaluatorInterface;
use Neos\Eel\ProtectedContext;
use Neos\Flow\Exception;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\ExceptionHandlers\ThrowingHandler;
use Neos\Fusion\Core\FusionConfiguration;
use Neos\Fusion\Core\FusionGlobals;
use Neos\Fusion\Core\IllegalEntryFusionPathValueException;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception\RuntimeException;
use Neos\Fusion\FusionObjects\ValueImplementation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class RuntimeTest extends UnitTestCase
{
    /**
     * if the rendering leads to an exception
     * the exception is transformed into 'content' by calling 'handleRenderingException'
     */
    #[Test]
    public function renderHandlesExceptionDuringRendering()
    {
        $runtimeException = new RuntimeException('I am a parent exception', 123, new Exception('I am a previous exception'), 'root');
        $runtime = $this->getMockBuilder(Runtime::class)->onlyMethods(['evaluate', 'handleRenderingException'])->disableOriginalConstructor()->getMock();
        $runtime->expects(self::any())->method('evaluate')->willThrowException($runtimeException);
        $runtime->expects(self::once())->method('handleRenderingException')->with('foo/bar', $runtimeException)->willReturn('Exception Message');

        $output = $runtime->render('foo/bar');

        self::assertEquals('Exception Message', $output);
    }

    /**
     * exceptions are rendered using the renderer from configuration
     *
     * if this handler throws exceptions, they are not handled
     */
    #[Test]
    public function handleRenderingExceptionThrowsException()
    {
        $this->expectException(Exception::class);
        $objectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->onlyMethods(['isRegistered', 'get'])->getMock();
        $runtimeException = new RuntimeException('I am a parent exception', 123, new Exception('I am a previous exception'), 'root');
        $runtime = new Runtime(FusionConfiguration::fromArray([]), FusionGlobals::createEmpty());
        $this->inject($runtime, 'objectManager', $objectManager);
        $exceptionHandlerSetting = 'settings';
        $runtime->injectSettings(['rendering' => ['exceptionHandler' => $exceptionHandlerSetting]]);

        $objectManager->expects(self::once())->method('isRegistered')->with($exceptionHandlerSetting)->willReturn(true);
        $objectManager->expects(self::once())->method('get')->with($exceptionHandlerSetting)->willReturn(new ThrowingHandler());

        $runtime->handleRenderingException('foo/bar', $runtimeException);
    }

    #[Test]
    public function evaluateProcessorForEelExpressionUsesProtectedContext()
    {
        $eelEvaluator = $this->createMock(EelEvaluatorInterface::class);
        $eelEvaluator->expects(self::once())->method('evaluate')->with(
            'foo + "89"',
            self::callback(fn (ProtectedContext $actualContext) => $actualContext->get('foo') === '19')
        );

        $runtime = new Runtime(FusionConfiguration::fromArray([]), FusionGlobals::createEmpty());
        $this->inject($runtime, 'eelEvaluator', $eelEvaluator);

        $runtime->pushContextArray(['foo' => '19']);

        $ref = (new \ReflectionClass($runtime))->getMethod('evaluateEelExpression');

        $ref->invoke($runtime, 'foo + "89"');
    }

    #[Test]
    public function evaluateWithCacheModeUncachedAndUnspecifiedContextThrowsException()
    {
        $this->expectException(\Neos\Fusion\Exception::class);
        $this->expectExceptionCode(1395922119);
        $runtime = new Runtime(FusionConfiguration::fromArray([
            'foo' => [
                'bar' => [
                    '__meta' => [
                        'cache' => [
                            'mode' => 'uncached'
                        ]
                    ]
                ]
            ]
        ]), FusionGlobals::createEmpty());

        $runtime->evaluate('foo/bar');
    }

    #[Test]
    public function renderRethrowsSecurityExceptions()
    {
        $this->expectException(\Neos\Flow\Security\Exception::class);
        $securityException = new \Neos\Flow\Security\Exception();
        $runtime = $this->getMockBuilder(Runtime::class)->onlyMethods(['evaluate', 'handleRenderingException'])->disableOriginalConstructor()->getMock();
        $runtime->expects(self::any())->method('evaluate')->willThrowException($securityException);

        $runtime->render('foo/bar');
    }

    #[Test]
    public function runtimeCurrentContextStackWorksSimplePushPop()
    {
        $runtime = new Runtime(FusionConfiguration::fromArray([]), FusionGlobals::createEmpty());

        self::assertSame([], $runtime->getCurrentContext(), 'context should be empty at start.');

        $runtime->pushContext('foo', 'bar');

        self::assertSame(['foo' => 'bar'], $runtime->getCurrentContext(), 'Runtime context has "foo => bar".');

        self::assertSame(['foo' => 'bar'], $runtime->popContext(), 'Runtime context returns "foo => bar" on pop.');

        self::assertSame([], $runtime->getCurrentContext(), 'Runtime context should be empty again at end.');
    }

    #[Test]
    public function runtimeCurrentContextStack3PushesAndPops()
    {
        $runtime = new Runtime(FusionConfiguration::fromArray([]), FusionGlobals::createEmpty());

        self::assertSame([], $runtime->getCurrentContext(), 'empty at start');

        $context1 = ['foo' => 'bar'];
        $runtime->pushContext('foo', 'bar');
        self::assertSame($context1, $runtime->getCurrentContext(), 'context1');

        $context2 = ['foo' => 123, 'buz' => 'baz'];
        $runtime->pushContextArray($context2);
        self::assertSame($context2, $runtime->getCurrentContext(), 'context2 (which overrides the only key "foo" of context1)');

        // $context3 = ['bla' => 456];
        $all3MergedContext = ['foo' => 123, 'buz' => 'baz', 'bla' => 456];
        $runtime->pushContext('bla', 456);
        self::assertSame($all3MergedContext, $runtime->getCurrentContext(), 'context1 context2 context3');
        self::assertSame($all3MergedContext, $runtime->popContext(), 'context1 context2 context3');

        self::assertSame($context2, $runtime->getCurrentContext(), 'context2');
        self::assertSame($context2, $runtime->popContext(), 'context2');

        self::assertSame($context1, $runtime->getCurrentContext(), 'context1');
        self::assertSame($context1, $runtime->popContext(), 'context1');

        self::assertSame([], $runtime->getCurrentContext(), 'empty at end');
    }

    #[Test]
    public function fusionContextIsNotAllowedToOverrideFusionGlobals()
    {
        $this->expectException(\Neos\Fusion\Exception::class);
        $this->expectExceptionMessage('Overriding Fusion global variable "request" via @context is not allowed.');
        $runtime = new Runtime(FusionConfiguration::fromArray([
            'foo' => [
                '__objectType' => 'Neos.Fusion:Value',
                '__meta' => [
                    'class' => ValueImplementation::class,
                    'context' => [
                        'request' => 'anything'
                    ]
                ]
            ]
        ]), FusionGlobals::fromArray(['request' => 'fixed']));
        $runtime->overrideExceptionHandler(new ThrowingHandler());

        $runtime->evaluate('foo');
    }

    #[Test]
    public function pushContextIsNotAllowedToOverrideFusionGlobals()
    {
        $this->expectException(\Neos\Fusion\Exception::class);
        $this->expectExceptionMessage('Overriding Fusion global variable "request" via @context is not allowed.');
        $runtime = new Runtime(FusionConfiguration::fromArray([]), FusionGlobals::fromArray(['request' => 'fixed']));

        $runtime->pushContext('request', 'anything');
    }

    #[Test]
    public function renderResponseIsNotAllowedToOverrideFusionGlobals()
    {
        $this->expectException(\Neos\Fusion\Exception::class);
        $this->expectExceptionMessage('Overriding Fusion global variable "request" via @context is not allowed.');
        $runtime = new Runtime(FusionConfiguration::fromArray([]), FusionGlobals::fromArray(['request' => 'fixed']));

        $runtime->renderEntryPathWithContext('foo', ['request' => 'anything']);
    }

    /**
     * Legacy compatible layer to possibly override fusion globals like "request".
     * This functionality is only allowed for internal packages.
     * Currently Neos.Fusion.Form overrides the request, and we need to keep this behaviour.
     *
     * {@link https://github.com/neos/fusion-form/blob/224a26afe11f182e6fc5d4bb27ce3f8d0f981ba2/Classes/Runtime/FusionObjects/RuntimeFormImplementation.php#L103}
     */
    #[Test]
    public function pushContextArrayIsAllowedToOverrideFusionGlobals()
    {
        $runtime = new Runtime(FusionConfiguration::fromArray([]), FusionGlobals::fromArray(['request' => 'fixed']));
        $runtime->pushContextArray(['bing' => 'beer', 'request' => 'anything']);
        self::assertTrue(true);
    }

    public static function renderStreamExamples(): iterable
    {
        yield 'simple string' => [
            'rawValue' => 'my string',
            'expectedStreamContents' => 'my string'
        ];

        yield 'string cast object (\Stringable)' => [
            'rawValue' => new class () implements \Stringable {
                public function __toString()
                {
                    return 'my string karsten';
                }
            },
            'expectedStreamContents' => 'my string karsten'
        ];

        yield 'empty string' => [
            'rawValue' => '',
            'expectedStreamContents' => ''
        ];

        yield 'null value' => [
            'rawValue' => null,
            'expectedStreamContents' => ''
        ];
    }

    public static function renderResponseExamples(): iterable
    {
        yield 'stringified http response string is upcasted' => [
            'rawValue' => <<<'TEXT'
            HTTP/1.1 418 OK
            Content-Type: text/html
            X-MyCustomHeader: marc

            <!DOCTYPE html>
            <head></head>
            <body>Hello World</body>
            TEXT,
            'expectedHttpResponseString' => <<<'TEXT'
            HTTP/1.1 418 OK
            Content-Type: text/html
            X-MyCustomHeader: marc

            <!DOCTYPE html>
            <head></head>
            <body>Hello World</body>
            TEXT
        ];
    }

    #[DataProvider('renderStreamExamples')]
    #[Test]
    public function renderEntryPathStream(mixed $rawValue, string $expectedStreamContents)
    {
        $runtime = $this->getMockBuilder(Runtime::class)
            ->setConstructorArgs([FusionConfiguration::fromArray([]), FusionGlobals::createEmpty()])
            ->onlyMethods(['render'])
            ->getMock();

        $runtime->expects(self::once())->method('render')->willReturn(
            $rawValue
        );

        $response = $runtime->renderEntryPathWithContext('path', []);

        self::assertInstanceOf(StreamInterface::class, $response);
        self::assertSame($expectedStreamContents, $response->getContents());
    }

    #[DataProvider('renderResponseExamples')]
    #[Test]
    public function renderEntryPathResponse(mixed $rawValue, string $expectedHttpResponseString)
    {
        $runtime = $this->getMockBuilder(Runtime::class)
            ->setConstructorArgs([FusionConfiguration::fromArray([]), FusionGlobals::createEmpty()])
            ->onlyMethods(['render'])
            ->getMock();

        $runtime->expects(self::once())->method('render')->willReturn(
            is_string($rawValue) ? str_replace("\n", "\r\n", $rawValue) : $rawValue
        );

        $response = $runtime->renderEntryPathWithContext('path', []);

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(str_replace("\n", "\r\n", $expectedHttpResponseString), Message::toString($response));
    }

    public static function renderResponseIllegalValueExamples(): iterable
    {
        yield 'array' => [
            'illegalValue' => ['my' => 'array', 'with' => 'values']
        ];

        yield '\stdClass' => [
            'illegalValue' => (object)[]
        ];

        yield '\JsonSerializable' => [
            'illegalValue' => new class () implements \JsonSerializable {
                public function jsonSerialize(): mixed
                {
                    return 123;
                }
            }
        ];

        yield 'any class' => [
            'illegalValue' => new class () {
            }
        ];

        yield 'boolean' => [
            'illegalValue' => false
        ];
    }

    #[DataProvider('renderResponseIllegalValueExamples')]
    #[Test]
    public function renderResponseThrowsIfNotStringable(mixed $illegalValue)
    {
        $this->expectException(IllegalEntryFusionPathValueException::class);
        $this->expectExceptionMessage(sprintf('Fusion entry path "path" is expected to render a compatible http response body: string|\Stringable|null. Got %s instead.', get_debug_type($illegalValue)));

        $runtime = $this->getMockBuilder(Runtime::class)
            ->setConstructorArgs([FusionConfiguration::fromArray([]), FusionGlobals::createEmpty()])
            ->onlyMethods(['render'])
            ->getMock();

        $runtime->expects(self::once())->method('render')->willReturn(
            $illegalValue
        );

        $runtime->renderEntryPathWithContext('path', []);
    }
}
