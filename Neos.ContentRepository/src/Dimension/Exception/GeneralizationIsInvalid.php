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

namespace Neos\ContentRepository\Dimension\Exception;

use Neos\ContentRepository\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\Dimension\ContentDimensionValue;

/**
 * The exception to be thrown if an invalid generalization of a content dimension value was tried to be initialized
 * @api
 */
class GeneralizationIsInvalid extends \DomainException
{
    public static function becauseComparedValueIsNoSpecialization(
        ContentDimensionValue $value,
        ContentDimensionValue $comparedValue,
        ContentDimensionIdentifier $dimensionIdentifier
    ): self {
        return new self(
            '"' . $comparedValue . '" is no specialization of "' . $value
                . '" in dimension "' . $dimensionIdentifier . '".'
        );
    }
}
