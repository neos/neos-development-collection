<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\NodeType;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeDeclaration;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReferenceTypeGraphTest extends TestCase
{
    /**
     * @param list<ReferenceTypeDeclaration> $referenceTypeDeclarations
     * @param array<string,array{
     *     superTypes: ReferenceTypeNameList,
     *     subTypes: ReferenceTypeNameList
     * }> $expectedSuperTypesAndSubTypesForReferenceTypeName indexed by reference type name
     */
    #[DataProvider('referenceSuperTypeDeclarationsProvider')]
    public function testGetSuperTypeNamesAndGetSubTypeNames(
        array $referenceTypeDeclarations,
        array $expectedSuperTypesAndSubTypesForReferenceTypeName,
    ): void {
        $referenceTypeGraph = new ReferenceTypeGraph(
            fn (): array => $referenceTypeDeclarations,
        );

        foreach (
            $expectedSuperTypesAndSubTypesForReferenceTypeName as $referenceTypeName => $expectedSuperTypesAndSubTypes
        ) {
            Assert::assertEquals(
                $expectedSuperTypesAndSubTypes['superTypes'],
                $referenceTypeGraph->getSuperTypeNames(ReferenceTypeName::fromString($referenceTypeName)),
            );
            Assert::assertEquals(
                $expectedSuperTypesAndSubTypes['subTypes'],
                $referenceTypeGraph->getSubTypeNames(ReferenceTypeName::fromString($referenceTypeName)),
            );
        }
    }

    /**
     * @return iterable<string,array{
     *     referenceTypeDeclarations: list<ReferenceTypeDeclaration>,
     *     expectedSuperTypesAndSubTypesForReferenceTypeName: array<string,array{
     *         superTypes: ReferenceTypeNameList,
     *         subTypes: ReferenceTypeNameList,
     *     }>
     * }>
     */
    public static function referenceSuperTypeDeclarationsProvider(): iterable
    {
        yield 'empty declaration set' => [
            'referenceTypeDeclarations' => [],
            'expectedSuperTypesAndSubTypesForReferenceTypeName' => [
                'Acme.Site:Reference' => [
                    'superTypes' => ReferenceTypeNameList::list(),
                    'subTypes' => ReferenceTypeNameList::list(),
                ],
            ],
        ];

        yield 'empty supertype list' => [
            'referenceTypeDeclarations' => [
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    superTypeNames: ReferenceTypeNameList::list(),
                ),
            ],
            'expectedSuperTypesAndSubTypesForReferenceTypeName' => [
                'Acme.Site:Reference' => [
                    'superTypes' => ReferenceTypeNameList::list(),
                    'subTypes' => ReferenceTypeNameList::list(),
                ],
            ],
        ];

        yield 'supertype list' => [
            'referenceTypeDeclarations' => [
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:RootReference'),
                    superTypeNames: ReferenceTypeNameList::list(),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:AnotherRootReference'),
                    superTypeNames: ReferenceTypeNameList::list(),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:ChildReference'),
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:RootReference'),
                        ReferenceTypeName::fromString('Acme.Site:AnotherRootReference'),
                    ),
                ),
            ],
            'expectedSuperTypesAndSubTypesForReferenceTypeName' => [
                'Acme.Site:ChildReference' => [
                    'superTypes' => ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:RootReference'),
                        ReferenceTypeName::fromString('Acme.Site:AnotherRootReference'),
                    ),
                    'subTypes' => ReferenceTypeNameList::list(),
                ],
                'Acme.Site:RootReference' => [
                    'superTypes' => ReferenceTypeNameList::list(),
                    'subTypes' => ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:ChildReference'),
                    ),
                ],
                'Acme.Site:AnotherRootReference' => [
                    'superTypes' => ReferenceTypeNameList::list(),
                    'subTypes' => ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:ChildReference'),
                    ),
                ],
            ],
        ];
    }
}
