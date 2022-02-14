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

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

use Neos\Flow\Annotations as Flow;

/**
 * The node variant selection strategy for node aggregates as selected when creating commands.
 * Used for calculating the affected dimension space points to e.g. build restriction relations to other node aggregates;
 * or to remove nodes.
 */
#[Flow\Proxy(false)]
enum NodeVariantSelectionStrategyIdentifier: string implements \JsonSerializable
{
    /**
     * The "only given" strategy, meaning only the given dimension space point is affected
     */
    case STRATEGY_ONLY_GIVEN_VARIANT = 'onlyGivenVariant';

    /**
     * The "virtual specializations" strategy, meaning only the specializations covered but unoccupied by this node aggregate are affected.
     */
    case STRATEGY_VIRTUAL_SPECIALIZATIONS = 'virtualSpecializations';

    /**
     * The "all specializations" strategy, meaning all specializations covered by this node aggregate are affected
     */
    case STRATEGY_ALL_SPECIALIZATIONS = 'allSpecializations';

    /**
     * The "all variants" strategy, meaning all dimension space points covered by this node aggregate are affected
     */
    case STRATEGY_ALL_VARIANTS = 'allVariants';

    public function equals(self $other): bool
    {
        return $this === $other;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
