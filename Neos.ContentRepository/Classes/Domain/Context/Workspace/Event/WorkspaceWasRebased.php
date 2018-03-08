<?php
namespace Neos\ContentRepository\Domain\Context\Workspace\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceTitle;
use Neos\EventSourcing\Event\EventInterface;

class WorkspaceWasRebased implements EventInterface
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
    private $currentContentStreamIdentifier;

    /**
     * WorkspaceWasRebased constructor.
     * @param WorkspaceName $workspaceName
     * @param ContentStreamIdentifier $currentContentStreamIdentifier
     */
    public function __construct(WorkspaceName $workspaceName, ContentStreamIdentifier $currentContentStreamIdentifier)
    {
        $this->workspaceName = $workspaceName;
        $this->currentContentStreamIdentifier = $currentContentStreamIdentifier;
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
    public function getCurrentContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->currentContentStreamIdentifier;
    }
}
