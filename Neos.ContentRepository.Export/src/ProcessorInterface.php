<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export;

interface ProcessorInterface
{
    /**
     * @param \Closure(Severity $severity, string $message): void $callback
     * @return void
     */
    public function onMessage(\Closure $callback): void;

    public function run(): ProcessorResult;
}
