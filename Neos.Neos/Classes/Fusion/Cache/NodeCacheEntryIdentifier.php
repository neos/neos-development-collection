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

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * The cache entry identifier data transfer object for nodes
 *
 * @Flow\Proxy(false)
 * @internal
 */
final readonly class NodeCacheEntryIdentifier implements CacheAwareInterface
{
    private function __construct(
        private string $value
    ) {
    }

    public static function fromNode(Node $node): self
    {
        return new self('Node_' . $node->workspaceName->value
            . '_' . $node->dimensionSpacePoint->hash
            . '_' .  $node->aggregateId->value);
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->value;
    }
}
