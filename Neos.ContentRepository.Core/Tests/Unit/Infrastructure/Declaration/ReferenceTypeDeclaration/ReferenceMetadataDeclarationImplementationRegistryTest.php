<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Infrastructure\Declaration\ReferenceTypesDeclaration;

use Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration\ReferenceMetadataDeclarationImplementationRegistry;
use Neos\ContentRepository\Core\Tests\Unit\Fixtures\AnotherExampleReferenceMetadataImplementation;
use Neos\ContentRepository\Core\Tests\Unit\Fixtures\ExampleReferenceMetadataImplementation;
use Neos\ContentRepository\Core\Tests\Unit\Fixtures\PostalAddress;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../../../Fixtures/AnotherExampleReferenceMetadataImplementation.php');
require_once(__DIR__ . '/../../../Fixtures/ExampleReferenceMetadataImplementation.php');
require_once(__DIR__ . '/../../../Fixtures/PostalAddress.php');

class ReferenceMetadataDeclarationImplementationRegistryTest extends TestCase
{
    #[DataProvider('registryProvider')]
    public function testFromArray(array $values, ReferenceMetadataDeclarationImplementationRegistry $expectedRegistry): void
    {
        Assert::assertEquals(
            $expectedRegistry,
            ReferenceMetadataDeclarationImplementationRegistry::fromArray($values),
        );
    }

    /**
     * @return iterable<string,array{
     *     values: array<string,bool>,
     *     expectedRegistry: ReferenceMetadataDeclarationImplementationRegistry
     * }>
     */
    public static function registryProvider(): iterable
    {
        yield 'emptyArray' => [
            'values' => [],
            'expectedRegistry' => ReferenceMetadataDeclarationImplementationRegistry::create(),
        ];

        yield 'mixedArray' => [
            'values' => [
                'string' => true,
                ExampleReferenceMetadataImplementation::class => false,
                AnotherExampleReferenceMetadataImplementation::class => true,
                PostalAddress::class => true,
            ],
            'expectedRegistry' => ReferenceMetadataDeclarationImplementationRegistry::create(
                AnotherExampleReferenceMetadataImplementation::class
            ),
        ];
    }
}
