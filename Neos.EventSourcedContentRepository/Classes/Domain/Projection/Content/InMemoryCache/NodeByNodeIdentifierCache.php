<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Projection\Content\InMemoryCache;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeIdentifier;

/**
 * NodeIdentifier -> Node cache
 *
 * also contains a *blacklist* of unknown NodeIdentifiers.
 */
final class NodeByNodeIdentifierCache
{
    protected $nodes = [];
    protected $nonExistingNodeIdentifiers = [];

    /**
     * @var bool
     */
    protected $isEnabled;

    public function __construct(bool $isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * basically like "contains"
     */
    public function knowsAbout(NodeIdentifier $nodeIdentifier): bool
    {
        if ($this->isEnabled === false) {
            return false;
        }

        $key = (string)$nodeIdentifier;
        return isset($this->nodes[$key]) || isset($this->nonExistingNodeIdentifiers[$key]);
    }

    public function add(NodeIdentifier $nodeIdentifier, NodeInterface $node): void
    {
        if ($this->isEnabled === false) {
            return;
        }

        $key = (string)$nodeIdentifier;
        $this->nodes[$key] = $node;
    }

    public function rememberNonExistingNodeIdentifier(NodeIdentifier $nodeIdentifier): void
    {
        if ($this->isEnabled === false) {
            return;
        }

        $key = (string)$nodeIdentifier;
        $this->nonExistingNodeIdentifiers[$key] = true;
    }

    public function get(NodeIdentifier $nodeIdentifier): ?NodeInterface
    {
        if ($this->isEnabled === false) {
            return null;
        }

        $key = (string)$nodeIdentifier;
        return $this->nodes[$key] ?? null;
    }
}
