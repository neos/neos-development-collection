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

namespace Neos\ContentRepository\Feature\WorkspacePublication\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\Feature\Common\NodeIdentifiersToPublishOrDiscard;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;

/**
 * Publish a set of nodes in a workspace
 */
final class PublishIndividualNodesFromWorkspace implements CommandInterface
{
    private function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly NodeIdentifiersToPublishOrDiscard $nodesToPublish,
        public readonly UserIdentifier $initiatingUserIdentifier,
        /**
         * during the publish process, we sort the events so that the events we want to publish
         * come first. In this process, two new content streams are generated:
         * - the first one contains all events which we want to publish
         * - the second one is based on the first one, and contains all the remaining events (which we want to keep
         *   in the user workspace).
         *
         * This property contains the identifier of the first content stream, so that this command
         * can run fully deterministic - we need this for the test cases.
         */
        public readonly ContentStreamIdentifier $contentStreamIdentifierForMatchingPart,
        /**
         * See the description of {@see $contentStreamIdentifierForMatchingPart}.
         *
         * This property contains the identifier of the second content stream, so that this command
         * can run fully deterministic - we need this for the test cases.
         */
        public readonly ContentStreamIdentifier $contentStreamIdentifierForRemainingPart
    ) {
    }

    public static function create(
        WorkspaceName $workspaceName,
        NodeIdentifiersToPublishOrDiscard $nodesToPublish,
        UserIdentifier $initiatingUserIdentifier
    ): self {
        return new self(
            $workspaceName,
            $nodesToPublish,
            $initiatingUserIdentifier,
            ContentStreamIdentifier::create(),
            ContentStreamIdentifier::create()
        );
    }

    /**
     * Call this method if you want to run this command fully deterministically, f.e. during test cases
     */
    public static function createFullyDeterministic(
        WorkspaceName $workspaceName,
        NodeIdentifiersToPublishOrDiscard $nodesToPublish,
        UserIdentifier $initiatingUserIdentifier,
        ContentStreamIdentifier $contentStreamIdentifierForMatchingPart,
        ContentStreamIdentifier $contentStreamIdentifierForRemainingPart
    ): self {
        return new self(
            $workspaceName,
            $nodesToPublish,
            $initiatingUserIdentifier,
            $contentStreamIdentifierForMatchingPart,
            $contentStreamIdentifierForRemainingPart
        );
    }
}
