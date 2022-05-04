<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Infrastructure\Property;

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
        $propertyConvertersConfiguration = (new PositionalArraySorter($this->propertyConvertersConfiguration))
            ->toArray();

        $normalizers = [];
        foreach ($propertyConvertersConfiguration as $propertyConverterConfiguration) {
            $normalizer = new $propertyConverterConfiguration['className'];
            if (!$normalizer instanceof NormalizerInterface && !$normalizer instanceof DenormalizerInterface) {
                throw new \InvalidArgumentException(
                    'Serializers can only be created of ' . NormalizerInterface::class
                        . ' and ' . DenormalizerInterface::class
                        . ', ' . get_class($normalizer) . ' given.',
                    1645386698
                );
            }
            $normalizers[] = $normalizer;
        }

        return new Serializer($normalizers);
    }
}
