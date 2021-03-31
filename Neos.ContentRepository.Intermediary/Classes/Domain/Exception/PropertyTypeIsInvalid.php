<?php declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain\Exception;

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a given property type is invalid
 *
 * @Flow\Proxy(false)
 */
final class PropertyTypeIsInvalid extends \DomainException
{
    public static function becauseItIsReference(): self
    {
        return new self('Property types "reference" and "references" are no longer supported, use reference relations instead.', 1597949281);
    }

    public static function becauseItIsUndefined(string $attemptedValue): self
    {
        return new self('Property type ' . $attemptedValue . ' is undefined and does not represent an existing class.', 1597950317);
    }
}
