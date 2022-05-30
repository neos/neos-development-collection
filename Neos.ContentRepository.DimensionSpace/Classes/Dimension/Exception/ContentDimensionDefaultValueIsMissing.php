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

namespace Neos\ContentRepository\DimensionSpace\Dimension\Exception;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValue;

/**
 * The exception to be thrown if a content dimension default value is missing
 */
class ContentDimensionDefaultValueIsMissing extends \DomainException
{
    public static function becauseItIsUndeclared(ContentDimensionIdentifier $dimensionIdentifier): self
    {
        return new self(
            'Content dimension ' . $dimensionIdentifier . ' has no default value declared.',
            1516639042
        );
    }

    public static function becauseItsDeclaredValueIsUndefined(
        ContentDimensionIdentifier $dimensionIdentifier,
        ContentDimensionValue $declaredDefaultValue
    ): self {
        return new self(
            'Content dimension ' . $dimensionIdentifier . ' has the undefined value '
                . $declaredDefaultValue . ' declared as default value.',
            1516639145
        );
    }
}
