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
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceTitle;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * TODO: WorkspaceWasPublished??
 *
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
     * TODO: FOR SOURCE AND FOR TARGET!?!?
     *
     * The new, empty content stream identifier of $sourceWorkspaceName, (after the publish was successful)
     *
     * @var ContentStreamIdentifier
     */
    private $currentContentStreamIdentifier;

    /**
     * The old content stream identifier of $sourceWorkspaceName (which is not active anymore now)
     *
     * @var ContentStreamIdentifier
     */
    private $previousContentStreamIdentifier;

    /**
     * WorkspaceWasPublished constructor.
     * @param WorkspaceName $sourceWorkspaceName
     * @param WorkspaceName $targetWorkspaceName
     * @param ContentStreamIdentifier $currentContentStreamIdentifier
     * @param ContentStreamIdentifier $previousContentStreamIdentifier
     */
    public function __construct(WorkspaceName $sourceWorkspaceName, WorkspaceName $targetWorkspaceName, ContentStreamIdentifier $currentContentStreamIdentifier, ContentStreamIdentifier $previousContentStreamIdentifier)
    {
        $this->sourceWorkspaceName = $sourceWorkspaceName;
        $this->targetWorkspaceName = $targetWorkspaceName;
        $this->currentContentStreamIdentifier = $currentContentStreamIdentifier;
        $this->previousContentStreamIdentifier = $previousContentStreamIdentifier;
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
    public function getCurrentContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->currentContentStreamIdentifier;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getPreviousContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->previousContentStreamIdentifier;
    }


}
