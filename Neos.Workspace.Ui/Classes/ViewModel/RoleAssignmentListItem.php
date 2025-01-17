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
use Neos\Neos\Domain\Model\WorkspaceRoleSubjectType;

/**
 * Derived from Neos\Neos\Domain\Model\WorkspaceRoleAssignment
 *
 * WHY: We need a custom DTO here, because
 *   - subject.value is either userId or group identifier (the latter would be fine, but for user we want the label)
 *   - role should be internationalized, maybe
 */
#[Flow\Proxy(false)]
final readonly class RoleAssignmentListItem
{
    // todo $subjectType and $subjectTypeValue are exact the same???
    public function __construct(
        public string $subjectValue,
        public string $subjectLabel,
        public string $subjectTypeValue,
        public string $roleLabel,
        public string $subjectType,
    ) {
    }
}
