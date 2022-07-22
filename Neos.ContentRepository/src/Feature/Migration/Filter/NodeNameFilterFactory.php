<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Filter;

use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;

/**
 * Filter nodes by node name.
 */
class NodeNameFilterFactory implements FilterFactoryInterface
{
    /**
     * @param array<string,string> $settings
     */
    public function build(array $settings): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface
    {
        $nodeName = NodeName::fromString($settings['nodeName']);

        return new class ($nodeName) implements NodeAggregateBasedFilterInterface {
            public function __construct(
                /**
                 * The node name to match on.
                 */
                private readonly NodeName $nodeName
            ) {
            }

            public function matches(ReadableNodeAggregateInterface $nodeAggregate): bool
            {
                if (!$nodeAggregate->getNodeName()) {
                    return false;
                }
                return $this->nodeName->jsonSerialize() === $nodeAggregate->getNodeName()->jsonSerialize();
            }
        };
    }
}
