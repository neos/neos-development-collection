<?php

/*
 * This file is part of the Neos.Restore.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\ViewModel\Restore;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Flow\Annotations as Flow;

/**
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
final readonly class RestoreListItem
{
    public function __construct(
        public NodeAggregateId $nodeAggregateId,
        public string $icon,
        public string $nodeTypeLabel,
        public RestoreListItemVariantDetailsCollection $details,
        public string $deletionUserName,
        public ?\DateTimeImmutable $deleteTime,
    ) {
    }

    public function getNodeAggregateId(): string
    {
        return $this->nodeAggregateId->jsonSerialize();
    }
}
