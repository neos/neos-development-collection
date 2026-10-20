<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\ReferenceTypeGraph;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\ReferenceTypeDeclarationDictionary;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\Flow\Configuration\ConfigurationManager;

readonly class DeclarationBasedReferenceTypeGraphFactory implements ReferenceTypeGraphFactoryInterface
{
    public function __construct(
        private ConfigurationManager $configurationManager,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ReferenceTypeGraph
    {
        /** @phpstan-ignore method.notFound (PHP 8.4 can do newLazyProxy) */
        return (new \ReflectionClass(ReferenceTypeGraph::class))->newLazyProxy(
            /** @phpstan-ignore neos.cr.internal */
            fn (): ReferenceTypeGraph => ReferenceTypeDeclarationDictionary::fromArray(
                $this->configurationManager->getConfiguration('ReferenceTypes'),
            )->expandToGraph(),
        );
    }
}
