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
class WorkspaceWasRebased implements DomainEventInterface
{
    /**
     * @var WorkspaceName
     */
    private $workspaceName;

    /**
     * The new content stream identifier (after the rebase was successful)
     *
     * @var ContentStreamIdentifier
     */
    private $newContentStreamIdentifier;

    /**
     * The old content stream identifier (which is not active anymore now)
     *
     * @var ContentStreamIdentifier
     */
    private $previousContentStreamIdentifier;


    /**
     * WorkspaceWasRebased constructor.
     * @param WorkspaceName $workspaceName
     * @param ContentStreamIdentifier $newContentStreamIdentifier
     * @param ContentStreamIdentifier $previousContentStreamIdentifier
     */
    public function __construct(WorkspaceName $workspaceName, ContentStreamIdentifier $newContentStreamIdentifier, ContentStreamIdentifier $previousContentStreamIdentifier)
    {
        $this->workspaceName = $workspaceName;
        $this->newContentStreamIdentifier = $newContentStreamIdentifier;
        $this->previousContentStreamIdentifier = $previousContentStreamIdentifier;
    }

    /**
     * @return WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getNewContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->newContentStreamIdentifier;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getPreviousContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->previousContentStreamIdentifier;
    }
}
