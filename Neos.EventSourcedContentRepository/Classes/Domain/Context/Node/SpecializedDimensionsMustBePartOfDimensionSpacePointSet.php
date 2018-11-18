<?php

namespace Neos\EventSourcedContentRepository\Domain\Context\Node;

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
 * The exception to be thrown if a specialized dimension is missing from the Dimension Space Point Set
 */
class SpecializedDimensionsMustBePartOfDimensionSpacePointSet extends Exception
{
}
