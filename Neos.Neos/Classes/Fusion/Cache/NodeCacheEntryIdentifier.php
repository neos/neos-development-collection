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

use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\Flow\Annotations as Flow;

/**
 * ForwardCompatibility for Neos 9.0
 *
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

    public static function fromNode(NodeInterface $node): self
    {
        return new self(NodePaths::generateContextPath(
            $node->getPath(),
            $node->getContext()->getWorkspaceName(),
            $node->getContext()->getDimensions()
        ));
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->value;
    }
}
