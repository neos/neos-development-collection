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
class WorkspaceWasPartiallyDiscarded implements DomainEventInterface
{
    private WorkspaceName $workspaceName;

    /**
     * The new content stream; containing the data which we want to keep
     */
    private ContentStreamIdentifier $newContentStreamIdentifier;

    /**
     * The old content stream, which contains ALL the data (discarded and non-discarded)
     */
    private ContentStreamIdentifier $previousContentStreamIdentifier;

    private UserIdentifier $initiatingUserIdentifier;

    /**
     * TODO build
     *
     * @var array|NodeAddress[]
     */
    private array $discardedNodeAddresses;

    public function __construct(
        WorkspaceName $workspaceName,
        ContentStreamIdentifier $newContentStreamIdentifier,
        ContentStreamIdentifier $previousContentStreamIdentifier,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->workspaceName = $workspaceName;
        $this->newContentStreamIdentifier = $newContentStreamIdentifier;
        $this->previousContentStreamIdentifier = $previousContentStreamIdentifier;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getNewContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->newContentStreamIdentifier;
    }

    public function getPreviousContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->previousContentStreamIdentifier;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }
}
