<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Fakes;

use Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration\ReferenceTypeDeclarationDictionary;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Exception\InvalidConfigurationException;
use Neos\ContentRepositoryRegistry\Factory\ReferenceTypeGraph\ReferenceTypeGraphFactoryInterface;
use Neos\Utility\PositionalArraySorter;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Fake factory for testing. Note that the factory MUST be initialised BEFORE the content repository is fetched.
 * Any changes after initialing a cr are lost UNLESS the content repository is rebuild.
 */
final class FakeReferenceTypeGraphFactory implements ReferenceTypeGraphFactoryInterface
{
    private static ?ReferenceTypeGraph $referenceTypeGraph = null;

    private static ?PropertyConverter $propertyConverter = null;

    /**
     * @param array<string,mixed> $contentRepositorySettings
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $options): ReferenceTypeGraph
    {
        if (!self::$propertyConverter) {
            self::$propertyConverter = new PropertyConverter(self::buildPropertySerializer($contentRepositoryId, $contentRepositorySettings));
        }
        if (self::$referenceTypeGraph) {
            return self::$referenceTypeGraph;
        }
        if (isset($options['referenceTypes'])) {
            // allows to be configured for testing
            $dictionary = ReferenceTypeDeclarationDictionary::fromArray($options['referenceTypes'], self::$propertyConverter);
        } else {
            $dictionary = ReferenceTypeDeclarationDictionary::create();
        }

        /** @phpstan-ignore neos.cr.internal (we are the test suite) */
        return $dictionary->expandToGraph();
    }

    /**
     * @param array<string,mixed> $referenceTypesToUse
     */
    public static function setConfiguration(array $referenceTypesToUse, PropertyConverter $propertyConverter): void
    {
        self::$propertyConverter = $propertyConverter;
        /** @phpstan-ignore neos.cr.internal (we are the test suite) */
        self::$referenceTypeGraph = ReferenceTypeDeclarationDictionary::fromArray(
            $referenceTypesToUse,
            self::$propertyConverter,
        )
            ->expandToGraph();
    }

    public static function setReferenceTypeGraph(ReferenceTypeGraph $referenceTypeGraph): void
    {
        self::$referenceTypeGraph = $referenceTypeGraph;
    }

    public static function reset(): void
    {
        self::$referenceTypeGraph = null;
    }

    /** @param array<string, mixed> $contentRepositorySettings */
    private static function buildPropertySerializer(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings): Serializer
    {
        (isset($contentRepositorySettings['propertyConverters']) && is_array($contentRepositorySettings['propertyConverters'])) || throw InvalidConfigurationException::fromMessage('Content repository "%s" does not have propertyConverters configured, or the value is no array.', $contentRepositoryId->value);
        $propertyConvertersConfiguration = (new PositionalArraySorter($contentRepositorySettings['propertyConverters']))->toArray();

        $normalizers = [];
        foreach ($propertyConvertersConfiguration as $propertyConverterConfiguration) {
            $normalizer = new $propertyConverterConfiguration['className']();
            if (!$normalizer instanceof NormalizerInterface && !$normalizer instanceof DenormalizerInterface) {
                throw InvalidConfigurationException::fromMessage('Serializers can only be created of %s and %s, %s given', NormalizerInterface::class, DenormalizerInterface::class, get_debug_type($normalizer));
            }
            $normalizers[] = $normalizer;
        }
        return new Serializer($normalizers);
    }
}
