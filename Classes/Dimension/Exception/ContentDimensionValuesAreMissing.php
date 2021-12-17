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

/**
 * The exception to be thrown if content dimension values are missing
 */
class ContentDimensionValuesAreMissing extends \DomainException
{
    public static function inADimension(ContentDimensionIdentifier $dimensionIdentifier): self
    {
        return new self(
            'Content dimension ' . $dimensionIdentifier . ' does not have any values defined',
            1516576422
        );
    }
}
