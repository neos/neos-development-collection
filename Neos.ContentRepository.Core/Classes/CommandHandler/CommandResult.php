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
 * TODO decide whether it might be useful to have some return type that tells something about the published events
 * (e.g. last sequence number) and maybe even about the updated/skipped projections
 * see discussion: https://github.com/neos/neos-development-collection/pull/5061#issuecomment-2117643465
 *
 * @internal this object will either be transformed into something useful and made API or deleted.
 */
final readonly class CommandResult
{
}
