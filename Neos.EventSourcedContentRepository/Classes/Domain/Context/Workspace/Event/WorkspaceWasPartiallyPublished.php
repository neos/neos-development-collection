<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class WorkspaceWasPartiallyPublished implements DomainEventInterface
{
    /**
     * From which workspace have changes been partially published?
     */
    private WorkspaceName $sourceWorkspaceName;

    /**
     * The target workspace where the changes have been published to.
     */
    private WorkspaceName $targetWorkspaceName;

    /**
     * The new content stream for the $sourceWorkspaceName
     */
    private ContentStreamIdentifier $newSourceContentStreamIdentifier;

    /**
     * The old content stream, which contains ALL the data (discarded and non-discarded)
     */
    private ContentStreamIdentifier $previousSourceContentStreamIdentifier;

    private UserIdentifier $initiatingUserIdentifier;

    /**
     * TODO build
     *
     * @var array|NodeAddress[]
     */
    private array $publishedNodeAddresses;

    public function __construct(
        WorkspaceName $sourceWorkspaceName,
        WorkspaceName $targetWorkspaceName,
        ContentStreamIdentifier $newSourceContentStreamIdentifier,
        ContentStreamIdentifier $previousSourceContentStreamIdentifier,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->sourceWorkspaceName = $sourceWorkspaceName;
        $this->targetWorkspaceName = $targetWorkspaceName;
        $this->newSourceContentStreamIdentifier = $newSourceContentStreamIdentifier;
        $this->previousSourceContentStreamIdentifier = $previousSourceContentStreamIdentifier;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public function getSourceWorkspaceName(): WorkspaceName
    {
        return $this->sourceWorkspaceName;
    }

    public function getTargetWorkspaceName(): WorkspaceName
    {
        return $this->targetWorkspaceName;
    }

    public function getNewSourceContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->newSourceContentStreamIdentifier;
    }

    public function getPreviousSourceContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->previousSourceContentStreamIdentifier;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }
}
