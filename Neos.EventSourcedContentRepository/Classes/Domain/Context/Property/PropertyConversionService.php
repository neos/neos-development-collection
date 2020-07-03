<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\Property;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Dto\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class PropertyConversionService
{

    public function serializePropertyValues(PropertyValuesToWrite $propertyValuesToWrite): SerializedPropertyValues
    {

    }
}
