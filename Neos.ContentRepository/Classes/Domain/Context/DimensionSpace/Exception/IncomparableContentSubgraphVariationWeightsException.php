<?php
namespace Neos\ContentRepository\Domain\Context\DimensionSpace\Exception;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Exception;

/**
 * The exception to be thrown if two content subgraph variation weights are to be compared that cannot, e.g. if they compose of different dimension combinations
 */
class IncomparableContentSubgraphVariationWeightsException extends Exception
{
}
