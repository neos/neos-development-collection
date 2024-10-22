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

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class EditWorkspaceDto
{
    public function __construct(
        public string $workspaceName,
        public string $workspaceTitle,
        public string $workspaceDescription,
        public string $workspaceOwnerId,
    )
    {
    }
}
