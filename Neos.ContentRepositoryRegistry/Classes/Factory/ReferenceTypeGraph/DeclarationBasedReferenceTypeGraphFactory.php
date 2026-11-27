<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\ReferenceTypeGraph;

use Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration\ReferenceTypeDeclarationDictionary;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Exception\InvalidConfigurationException;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Utility\PositionalArraySorter;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

readonly class DeclarationBasedReferenceTypeGraphFactory implements ReferenceTypeGraphFactoryInterface
{
    public function __construct(
        private ConfigurationManager $configurationManager,
    ) {
    }

    /**
     * @param array<string, mixed> $contentRepositorySettings
     * @param array<string, mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $options): ReferenceTypeGraph
    {
        /** @phpstan-ignore method.notFound (PHP 8.4 can do newLazyProxy) */
        return (new \ReflectionClass(ReferenceTypeGraph::class))->newLazyProxy(
            /** @phpstan-ignore neos.cr.internal */
            fn (): ReferenceTypeGraph => ReferenceTypeDeclarationDictionary::fromArray(
                $this->configurationManager->getConfiguration('ReferenceTypes'),
                new PropertyConverter($this->buildPropertySerializer($contentRepositoryId, $contentRepositorySettings))
            )->expandToGraph(),
        );
    }

    /** @param array<string, mixed> $contentRepositorySettings */
    private function buildPropertySerializer(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings): Serializer
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
