<?php
declare(strict_types=1);
namespace Neos\ESCR\Export;

use Neos\ESCR\Export\Middleware\Context;
use Neos\ESCR\Export\Middleware\MiddlewareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class Handler
{
    private ?\Closure $onStartCallback = null;
    private ?\Closure $onStepCallback = null;

    /**
     * @param array<MiddlewareInterface> $middlewares
     */
    private function __construct(
        private readonly Context $context,
        private readonly array $middlewares,
    ) {}

    public static function fromContextAndMiddlewares(Context $context, MiddlewareInterface ...$middlewares): self
    {
        return new self($context, $middlewares);
    }

    /**
     * @param \Closure(int $numberOfSteps, Context $context): void $callback
     */
    public function onStart(\Closure $callback): void
    {
        $this->onStartCallback = $callback;
    }

    /**
     * @param \Closure(MiddlewareInterface $middleware): void $callback
     */
    public function onStep(\Closure $callback): void
    {
        $this->onStepCallback = $callback;
    }

    /**
     * @param \Closure(string $message): void $callback
     */
    public function onMessage(\Closure $callback): void
    {
        $this->context->onMessage($callback);
    }

    public function processImport(): void
    {
        $this->process(function(MiddlewareInterface $middleware) {
            $middleware->processImport($this->context);
        });
    }

    public function processExport(): void
    {
        $this->process(function(MiddlewareInterface $middleware) {
            $middleware->processExport($this->context);
        });
    }

    // ------------------

    /**
     * @param \Closure(MiddlewareInterface $middleware): void $callback
     */
    private function process(\Closure $callback): void
    {
        $this->onStartCallback === null || ($this->onStartCallback)(count($this->middlewares), $this->context);
        foreach ($this->middlewares as $middleware) {
            $this->onStepCallback === null || ($this->onStepCallback)($middleware);
            $callback($middleware);
        }
    }
}
