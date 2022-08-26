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

namespace Neos\ContentRepository\Feature\NodeTypeChange\Command;


/**
 * The strategy how to handle node type constraint conflicts with already present child nodes
 * when changing a node aggregate's type.
 *
 * - delete will delete all newly disallowed child nodes
 */
enum NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy: string implements \JsonSerializable
{
    /**
     * This strategy means "we remove all children / grandchildren nodes which do not match the constraint"
     */
    case STRATEGY_DELETE = 'delete';

    /**
     * This strategy means "we only change the NodeAggregateType if all constraints of parents
     * AND children and grandchildren are still respected."
     */
    case STRATEGY_HAPPY_PATH = 'happypath';

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
