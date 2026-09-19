<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\Shared;

/**
 * @internal CR upgrade internals
 */
trait OutputMessageTrait
{
    final protected function log(string $message): void
    {
        ($this->outputFn)($message);
    }
}
