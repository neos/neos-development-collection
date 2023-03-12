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

namespace Neos\ContentRepository\NodeMigration\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;

/**
 * Filter nodes by node type.
 */
class NodeTypeFilterFactory implements FilterFactoryInterface
{
    public function __construct(private readonly NodeTypeManager $nodeTypeManager)
    {
    }

    /**
     * @param array<string,mixed> $settings
     */
    public function build(array $settings): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface
    {
        $nodeType = NodeTypeName::fromString($settings['nodeType']);
        $withSubTypes = false;
        $exclude = false;

        if (isset($settings['withSubTypes'])) {
            $withSubTypes = (bool)$settings['withSubTypes'];
        }
        if (isset($settings['exclude'])) {
            $exclude = (bool)$settings['exclude'];
        }

        return new class (
            $nodeType,
            $withSubTypes,
            $exclude,
            $this->nodeTypeManager
        ) implements NodeAggregateBasedFilterInterface {
            public function __construct(
                /**
                 * Sets the node type name to match on.
                 */
                private readonly NodeTypeName $nodeTypeName,
                /**
                 * Whether the filter should match also on all subtypes of the configured
                 * node type.
                 *
                 * Note: This can only be used with node types still available in the
                 * system!
                 */
                private readonly bool $withSubTypes,
                /**
                 * Whether the filter should exclude the given NodeType instead of including only this node type.
                 */
                private readonly bool $exclude,
                private readonly NodeTypeManager $nodeTypeManager
            ) {
            }

            public function matches(NodeAggregate $nodeAggregate): bool
            {
                $nodeTypes = [$this->nodeTypeName];
                if ($this->withSubTypes) {
                    foreach ($this->nodeTypeManager->getSubNodeTypes($this->nodeTypeName) as $nodeType) {
                        $nodeTypes[] = $nodeType->getName();
                    }
                }

                if ($this->exclude) {
                    return !in_array($nodeAggregate->nodeTypeName->value, $nodeTypes);
                } else {
                    // non-negated
                    return in_array($nodeAggregate->nodeTypeName->value, $nodeTypes);
                }
            }
        };
    }
}
