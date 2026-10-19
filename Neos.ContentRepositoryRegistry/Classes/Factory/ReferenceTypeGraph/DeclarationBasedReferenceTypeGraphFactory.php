<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\ReferenceTypeGraph;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\DeclarationBasedReferenceTypeGraph;
use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\ReferenceTypeDeclarationDictionary;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraphInterface;
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
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ReferenceTypeGraphInterface
    {
        return DeclarationBasedReferenceTypeGraph::createLazy(
            dictionary: ReferenceTypeDeclarationDictionary::fromArray(
                $this->configurationManager->getConfiguration('ReferenceTypes'),
            ),
        );
    }
}
