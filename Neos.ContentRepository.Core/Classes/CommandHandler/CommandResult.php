<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\ContentRepository;

/**
 * Was the result of the {@see ContentRepository::handle()} method.
 * Previously one would need this to be able to block until the projections were updated.
 *
 * This will no longer be required in the future see https://github.com/neos/neos-development-collection/pull/4988
 *
 * @deprecated this b/c layer will be removed with the next beta or before Neos 9 final release
 * @api
 */
final readonly class CommandResult
{
    /**
     * We block by default thus you must not call this method or use this legacy stub
     * @deprecated this b/c layer will be removed with the next beta or before Neos 9 final release
     */
    public function block(): void
    {
    }
}
