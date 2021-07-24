<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Migration\Filters;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Filter nodes by node name.
 */
class NodeName implements NodeAggregateBasedFilterInterface
{
    /**
     * The node name to match on.
     */
    protected \Neos\ContentRepository\Domain\NodeAggregate\NodeName $nodeName;

    /**
     * Sets the node type name to match on.
     * @param string $nodeName
     */
    public function setNodeName(string $nodeName): void
    {
        $this->nodeName = \Neos\ContentRepository\Domain\NodeAggregate\NodeName::fromString($nodeName);
    }

    public function matches(ReadableNodeAggregateInterface $nodeAggregate): bool
    {
        if (!$nodeAggregate->getNodeName()) {
            return false;
        }
        return $this->nodeName->jsonSerialize() === $nodeAggregate->getNodeName()->jsonSerialize();
    }
}
