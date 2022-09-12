<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Infrastructure;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Utility\PositionalArraySorter;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @Flow\Scope("singleton")
 * @internal
 */
final class PropertyConversionSerializerFactory
{
    /**
     * @Flow\InjectConfiguration(path="propertyConverters")
     * @var array<int,class-string>
     */
    protected array $propertyConvertersConfiguration;

    public function buildSerializer(): Serializer
    {

    }
}
