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

namespace Neos\Neos\Domain\Workspace;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * The command DTO to communicate publication of all changes recorded in a given workspace
 *
 * @internal for communication within Neos only
 */
#[Flow\Proxy(false)]
final readonly class PublishAllChanges
{
    public function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public WorkspaceName $workspaceName,
    ) {
    }

    /**
     * @param array<string,string> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            ContentRepositoryId::fromString($values['contentRepositoryId']),
            WorkspaceName::fromString($values['workspaceName']),
        );
    }
}
