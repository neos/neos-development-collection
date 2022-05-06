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

namespace Neos\Neos\Domain\Model;

use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\Projection\Content\NodeInterface;

/**
 * The cache entry identifier data transfer object for nodes
 */
final class NodeCacheEntryIdentifier implements CacheAwareInterface
{
    private function __construct(
        private readonly string $value
    ) {
    }

    public static function fromNode(NodeInterface $node): self
    {
        return new self('Node_' . $node->getContentStreamIdentifier()->getValue()
            . '_' . $node->getDimensionSpacePoint()->hash
            . '_' .  $node->getNodeAggregateIdentifier()->getValue());
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->value;
    }
}
