<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Fakes;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\ReferenceTypeDeclarationDictionary;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\ReferenceTypeGraph\ReferenceTypeGraphFactoryInterface;

/**
 * Fake factory for testing. Note that the factory MUST be initialised BEFORE the content repository is fetched.
 * Any changes after initialing a cr are lost UNLESS the content repository is rebuild.
 */
final class FakeReferenceTypeGraphFactory implements ReferenceTypeGraphFactoryInterface
{
    private static ?ReferenceTypeGraph $referenceTypeGraph = null;

    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ReferenceTypeGraph
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

        /** @phpstan-ignore neos.cr.internal (we are the test suite) */
        return $dictionary->expandToGraph();
    }

    /**
     * @param array<string,mixed> $referenceTypesToUse
     */
    public static function setConfiguration(array $referenceTypesToUse): void
    {
        /** @phpstan-ignore neos.cr.internal (we are the test suite) */
        self::$referenceTypeGraph = ReferenceTypeDeclarationDictionary::fromArray($referenceTypesToUse)
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
}
