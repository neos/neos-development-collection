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
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class WorkspaceWasPublished implements DomainEventInterface
{
    /**
     * From which workspace have changes been published?
     *
     * @var WorkspaceName
     */
    private $sourceWorkspaceName;

    /**
     * The target workspace where the changes have been published to.
     *
     * @var WorkspaceName
     */
    private $targetWorkspaceName;

    /**
     * The new, empty content stream identifier of $sourceWorkspaceName, (after the publish was successful)
     *
     * @var ContentStreamIdentifier
     */
    private $newSourceContentStreamIdentifier;

    /**
     * The old content stream identifier of $sourceWorkspaceName (which is not active anymore now)
     *
     * @var ContentStreamIdentifier
     */
    private $previousSourceContentStreamIdentifier;

    /**
     * WorkspaceWasPublished constructor.
     * @param WorkspaceName $sourceWorkspaceName
     * @param WorkspaceName $targetWorkspaceName
     * @param ContentStreamIdentifier $newSourceContentStreamIdentifier
     * @param ContentStreamIdentifier $previousSourceContentStreamIdentifier
     */
    public function __construct(WorkspaceName $sourceWorkspaceName, WorkspaceName $targetWorkspaceName, ContentStreamIdentifier $newSourceContentStreamIdentifier, ContentStreamIdentifier $previousSourceContentStreamIdentifier)
    {
        $this->sourceWorkspaceName = $sourceWorkspaceName;
        $this->targetWorkspaceName = $targetWorkspaceName;
        $this->newSourceContentStreamIdentifier = $newSourceContentStreamIdentifier;
        $this->previousSourceContentStreamIdentifier = $previousSourceContentStreamIdentifier;
    }


    /**
     * @return WorkspaceName
     */
    public function getSourceWorkspaceName(): WorkspaceName
    {
        return $this->sourceWorkspaceName;
    }

    /**
     * @return WorkspaceName
     */
    public function getTargetWorkspaceName(): WorkspaceName
    {
        return $this->targetWorkspaceName;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getNewSourceContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->newSourceContentStreamIdentifier;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getPreviousSourceContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->previousSourceContentStreamIdentifier;
    }
}
