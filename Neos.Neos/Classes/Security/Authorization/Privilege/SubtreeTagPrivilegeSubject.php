<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Security\Authorization\Privilege;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;

/**
 * A subject for the {@see SubtreeTagPrivilege}
 */
final readonly class SubtreeTagPrivilegeSubject implements PrivilegeSubjectInterface
{
    public function __construct(
        public SubtreeTag $subTreeTag,
        public ContentRepositoryId|null $contentRepositoryId = null,
    ) {
    }
}
