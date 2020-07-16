<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Property;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\Dto\PropertyValuesToWrite;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcing\EventStore\Normalizer\ProxyAwareObjectNormalizer;
use Neos\EventSourcing\EventStore\Normalizer\ValueObjectNormalizer;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\PositionalArraySorter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @Flow\Scope("singleton")
 * @internal
 */
final class PropertyConversionSerializerFactory
{

    /**
     * @var array
     * @Flow\InjectConfiguration(path="propertyConversion.serializationNormalizers")
     */
    protected $serializationNormalizersConfiguration;

    public function buildSerializer(): Serializer
    {
        $serializationNormalizersConfiguration = (new PositionalArraySorter($this->serializationNormalizersConfiguration))->toArray();

        $normalizers = [];
        foreach ($serializationNormalizersConfiguration as $serializationNormalizerConfiguration) {
            $normalizers[] = new $serializationNormalizerConfiguration['className'];
        }

        return new Serializer($normalizers);
    }
}
