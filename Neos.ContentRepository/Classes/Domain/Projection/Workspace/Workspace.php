<?php
namespace Neos\ContentRepository\Domain\Projection\Workspace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcing\Annotations as CQRS;
use Neos\Flow\Annotations as Flow;

/**
 * Workspace Read Model
 *
 * @Flow\Entity
 * @CQRS\ReadModel
 * @ORM\Table(name="neos_contentrepository_projection_workspace_v1")
 */
final class Workspace
{

    /**
     * @ORM\Id
     * @var string
     */
    public $workspaceName;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    public $baseWorkspaceName;

    /**
     * @var string
     */
    public $workspaceTitle;

    /**
     * @var string
     */
    public $workspaceDescription;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    public $workspaceOwner;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    public $currentContentStreamIdentifier;

    /**
     * Checks if this workspace is shared across all editors
     *
     * @return boolean
     */
    public function isInternalWorkspace()
    {
        return $this->baseWorkspaceName !== null && $this->workspaceOwner === null;
    }

    /**
     * Checks if this workspace is public to everyone, even without authentication
     *
     * @return boolean
     */
    public function isPublicWorkspace()
    {
        return $this->baseWorkspaceName === null && $this->workspaceOwner === null;
    }

    /**
     * Checks if the workspace is the root workspace
     *
     * @return boolean
     */
    public function isRootWorkspace()
    {
        return $this->baseWorkspaceName === null;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getCurrentContentStreamIdentifier(): ContentStreamIdentifier
    {
        return new ContentStreamIdentifier($this->currentContentStreamIdentifier);
    }

    /**
     * @return WorkspaceName|null
     */
    public function getBaseWorkspaceName(): ?WorkspaceName
    {
        return $this->baseWorkspaceName ? new WorkspaceName($this->baseWorkspaceName) : null;
    }

    /**
     * @return WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return new WorkspaceName($this->workspaceName);
    }
}
