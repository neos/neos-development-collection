<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\DimensionSpace\Exception;

use Neos\ContentRepository\DimensionSpace\ContentSubgraphVariationWeight;

/**
 * The exception to be thrown if two content subgraph variation weights are to be compared that cannot,
 * e.g. if they compose of different dimension combinations
 * @api
 */
class ContentSubgraphVariationWeightsAreIncomparable extends \DomainException
{
    public static function butWereAttemptedTo(
        ContentSubgraphVariationWeight $first,
        ContentSubgraphVariationWeight $second
    ): self {
        return new self(
            'Weights ' . $first . ' and ' . $second . ' cannot be compared.',
            1517474233
        );
    }
}
