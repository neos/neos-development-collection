<?php
declare(strict_types=1);

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

use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a property was attempted to be set but cannot be
 *
 * @Flow\Proxy(false)
 */
final class PropertyCannotBeSet extends \DomainException
{
    public static function becauseTheValueDoesNotMatchTheConfiguredType(PropertyName $propertyName, string $attemptedType, string $configuredType): self
    {
        return new self(
            'Property "' . $propertyName . '" cannot be set to a value of type "' . $attemptedType . '", must be of type "' . $configuredType . '"',
            1615466573
        );
    }
}
