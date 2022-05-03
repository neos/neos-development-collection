<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware;

use League\Flysystem\Filesystem;
use Neos\ESCR\Export\ValueObject\Parameters;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class Context
{
    private ?\Closure $onMessageCallback = null;

    public function __construct(
        public readonly Filesystem $files,
        public readonly Parameters $parameters,
    ) {}

    /**
     * @param \Closure(string $message): void $callback
     */
    public function onMessage(\Closure $callback): void
    {
        $this->onMessageCallback = $callback;
    }

    public function report(string $message): void
    {
        if ($this->onMessageCallback !== null) {
            ($this->onMessageCallback)($message);
        }
    }

}
