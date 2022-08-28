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

/**
 * The exception to be thrown if an invalid content dimension identifier was attempted to be initialized
 * @api
 */
class ContentDimensionIdentifierIsInvalid extends \DomainException
{
    public static function becauseItMustNotBeEmpty(): self
    {
        return new self(
            'Content dimension identifiers must not be empty.',
            1515166615
        );
    }
}
