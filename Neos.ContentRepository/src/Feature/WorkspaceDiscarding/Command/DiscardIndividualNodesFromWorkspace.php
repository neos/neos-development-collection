<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\WorkspaceDiscarding\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\NodeAddress;

/**
 * Discard a set of nodes in a workspace
 */
final class DiscardIndividualNodesFromWorkspace implements CommandInterface
{
    /**
     * @param array<int,NodeAddress> $nodeAddresses
     */
    private function __construct(
        public readonly WorkspaceName $workspaceName,
        /**
         * @var array<int,NodeAddress>
         */
        public readonly array $nodeAddresses,
        public readonly UserIdentifier $initiatingUserIdentifier,
        /**
         * Content Stream Identifier of the newly created fork, which contains the remaining changes which were not removed
         */
        public readonly ContentStreamIdentifier $newContentStreamIdentifier
    ) {
    }

    /**
     * @param array<int,NodeAddress> $nodeAddresses
     */
    public static function create(
        WorkspaceName $workspaceName,
        array $nodeAddresses,
        UserIdentifier $initiatingUserIdentifier
    ): self {
        return new self(
            $workspaceName,
            $nodeAddresses,
            $initiatingUserIdentifier,
            ContentStreamIdentifier::create()
        );
    }

    /**
     * Call this method if you want to run this command fully deterministically, f.e. during test cases
     * @param array<int,NodeAddress> $nodeAddresses
     */
    public static function createFullyDeterministic(
        WorkspaceName $workspaceName,
        array $nodeAddresses,
        UserIdentifier $initiatingUserIdentifier,
        ContentStreamIdentifier $newContentStreamIdentifier
    ): self {
        return new self($workspaceName, $nodeAddresses, $initiatingUserIdentifier, $newContentStreamIdentifier);
    }
}
