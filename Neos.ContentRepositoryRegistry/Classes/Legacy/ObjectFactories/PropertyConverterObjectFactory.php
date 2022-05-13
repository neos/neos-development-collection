<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;
/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Symfony\Component\Serializer\Serializer;

final class PropertyConverterObjectFactory
{
    public function __construct(
        private readonly Serializer $propertySerializer,
    )
    {
    }

    public function buildPropertyConverter(): PropertyConverter
    {
        return new PropertyConverter($this->propertySerializer);
    }
}
