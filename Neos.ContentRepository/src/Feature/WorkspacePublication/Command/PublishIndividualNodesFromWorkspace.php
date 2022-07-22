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

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\Flow\Annotations as Flow;

/**
 * Publish a set of nodes in a workspace
 */
#[Flow\Proxy(false)]
final class PublishIndividualNodesFromWorkspace
{
    private WorkspaceName $workspaceName;

    /**
     * @var array<int,NodeAddress>
     */
    private array $nodeAddresses;

    private UserIdentifier $initiatingUserIdentifier;

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
    private ContentStreamIdentifier $contentStreamIdentifierForMatchingPart;

    /**
     * See the description of {@see $contentStreamIdentifierForMatchingPart}.
     *
     * This property contains the identifier of the second content stream, so that this command
     * can run fully deterministic - we need this for the test cases.
     */
    private ContentStreamIdentifier $contentStreamIdentifierForRemainingPart;

    /**
     * @param array<int,NodeAddress> $nodeAddresses
     */
    private function __construct(
        WorkspaceName $workspaceName,
        array $nodeAddresses,
        UserIdentifier $initiatingUserIdentifier,
        ContentStreamIdentifier $contentStreamIdentifierForMatchingPart,
        ContentStreamIdentifier $contentStreamIdentifierForRemainingPart
    ) {
        $this->workspaceName = $workspaceName;
        $this->nodeAddresses = $nodeAddresses;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->contentStreamIdentifierForMatchingPart = $contentStreamIdentifierForMatchingPart;
        $this->contentStreamIdentifierForRemainingPart = $contentStreamIdentifierForRemainingPart;
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
            ContentStreamIdentifier::create(),
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
        ContentStreamIdentifier $contentStreamIdentifierForMatchingPart,
        ContentStreamIdentifier $contentStreamIdentifierForRemainingPart
    ): self {
        return new self(
            $workspaceName,
            $nodeAddresses,
            $initiatingUserIdentifier,
            $contentStreamIdentifierForMatchingPart,
            $contentStreamIdentifierForRemainingPart
        );
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * @return array|NodeAddress[]
     */
    public function getNodeAddresses(): array
    {
        return $this->nodeAddresses;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }


    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifierForMatchingPart(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifierForMatchingPart;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifierForRemainingPart(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifierForRemainingPart;
    }
}
