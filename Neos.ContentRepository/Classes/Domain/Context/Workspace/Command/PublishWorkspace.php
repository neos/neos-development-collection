<?php
namespace Neos\ContentRepository\Domain\Context\Workspace\Command;

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

/**
 * Publish a workspace
 */
final class PublishWorkspace
{
    /**
     * @var WorkspaceName
     */
    private $workspaceName;

    /**
     * PublishWorkspace constructor.
     * @param WorkspaceName $workspaceName
     */
    public function __construct(WorkspaceName $workspaceName)
    {
        $this->workspaceName = $workspaceName;
    }

    /**
     * @return WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }
}
