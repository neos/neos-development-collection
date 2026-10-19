<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Fakes;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\DeclarationBasedReferenceTypeGraph;
use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\ReferenceTypeDeclarationDictionary;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraphInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\ReferenceTypeGraph\ReferenceTypeGraphFactoryInterface;

/**
 * Fake factory for testing. Note that the factory MUST be initialised BEFORE the content repository is fetched.
 * Any changes after initialing a cr are lost UNLESS the content repository is rebuild.
 */
final class FakeReferenceTypeGraphFactory implements ReferenceTypeGraphFactoryInterface
{
    private static ?ReferenceTypeGraphInterface $referenceTypeGraph = null;

    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ReferenceTypeGraphInterface
    {
        if (self::$referenceTypeGraph) {
            return self::$referenceTypeGraph;
        }
        if (isset($options['referenceTypes'])) {
            // allows to be configured for testing
            $dictionary = ReferenceTypeDeclarationDictionary::fromArray($options['referenceTypes']);
        } else {
            $dictionary = ReferenceTypeDeclarationDictionary::create();
        }

        return DeclarationBasedReferenceTypeGraph::create($dictionary);
    }

    /**
     * @param array<string,mixed> $referenceTypesToUse
     */
    public static function setConfiguration(array $referenceTypesToUse): void
    {
        self::$referenceTypeGraph = DeclarationBasedReferenceTypeGraph::create(
            ReferenceTypeDeclarationDictionary::fromArray($referenceTypesToUse)
        );
    }

    public static function setReferenceTypeGraph(ReferenceTypeGraphInterface $referenceTypeGraph): void
    {
        self::$referenceTypeGraph = $referenceTypeGraph;
    }

    public static function reset(): void
    {
        self::$referenceTypeGraph = null;
    }
}
