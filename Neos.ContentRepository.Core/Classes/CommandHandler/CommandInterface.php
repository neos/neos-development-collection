<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;

/**
 * Common interface for all commands of the content repository
 *
 * Note: Some commands also implement the {@see RebasableToOtherWorkspaceInterface}
 * others are converted to the rebasable counter-part at command handling time, serializing their state to make them deterministic
 *
 * @internal sealed interface. Custom commands cannot be handled and are no extension point!
 */
interface CommandInterface
{
    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self;
}
