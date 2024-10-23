<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\ViewModel;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceTitle;

#[Flow\Proxy(false)]
final readonly class EditWorkspaceFormData
{
    public function __construct(
        public WorkspaceName        $workspaceName,
        public WorkspaceTitle       $workspaceTitle,
        public WorkspaceDescription $workspaceDescription,
        public bool                 $workspaceHasChanges,
        public WorkspaceName        $baseWorkspaceName,
        /**
         * Options for the baseWorkspace selector where the key is the workspace name and the value is the workspace title.
         * @var array<string, string>
         */
        public array                $baseWorkspaceOptions,
    )
    {
    }
}
