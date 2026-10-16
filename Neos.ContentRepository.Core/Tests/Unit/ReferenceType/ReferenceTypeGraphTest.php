<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\ReferenceType;

use Neos\ContentRepository\Core\ReferenceType\Exception\ReferenceTypeDeclarationIsInvalid;
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
     */
    #[DataProvider('referenceTypeDeclarationProvider')]
    public function testFromDeclarations(
        array $referenceTypeDeclarations,
        ?ReferenceTypeGraphProperties $expectedResult,
        ?\Throwable $expectedException,
    ): void {
        try {
            $actualResult = ReferenceTypeGraph::fromDeclarations($referenceTypeDeclarations);
            $actualException = null;
        } catch (\Throwable $actualException) {
            $actualResult = null;
        }

        if ($expectedResult) {
            Assert::assertTrue($expectedResult->equals($actualResult));
        } else {
            Assert::assertSame($expectedResult, $actualResult);
        }
        Assert::assertEquals($expectedException, $actualException);
    }

    /**
     * @return iterable<string,array{
     *     referenceTypeDeclarations: list<ReferenceTypeDeclaration>,
     *     expectedResult: ?ReferenceTypeGraphProperties,
     *     expectedException: ?\Throwable,
     * }>
     */
    public static function referenceTypeDeclarationProvider(): iterable
    {
        yield 'empty set' => [
            'referenceTypeDeclarations' => [],
            'expectedResult' => new ReferenceTypeGraphProperties(
                superTypeNames: [],
                subTypesNames: [],
                referenceTypes: [],
                referenceTypeDeclarations: [],
            ),
            'expectedException' => null,
        ];

        yield 'unknown supertype' => [
            'referenceTypeDeclarations' => [
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:Unknown'),
                    )
                ),
            ],
            'expectedResult' => null,
            'expectedException' => ReferenceTypeDeclarationIsInvalid::becauseItHasUnknownSuperTypes(
                ReferenceTypeName::fromString('Acme.Site:Reference'),
                ReferenceTypeNameList::list(
                    ReferenceTypeName::fromString('Acme.Site:Unknown'),
                ),
            ),
        ];

        yield 'final supertype' => [
            'referenceTypeDeclarations' => [
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:FinalReference'),
                    )
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:FinalReference'),
                    isAbstract: false,
                    isFinal: true,
                    superTypeNames: ReferenceTypeNameList::list()
                ),
            ],
            'expectedResult' => null,
            'expectedException' => ReferenceTypeDeclarationIsInvalid::becauseItHasFinalSuperTypes(
                ReferenceTypeName::fromString('Acme.Site:Reference'),
                ReferenceTypeNameList::list(
                    ReferenceTypeName::fromString('Acme.Site:FinalReference'),
                ),
            ),
        ];
    }

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
        $referenceTypeGraph = ReferenceTypeGraph::fromDeclarations($referenceTypeDeclarations);

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
                    isAbstract: false,
                    isFinal: false,
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
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:AnotherRootReference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:ChildReference'),
                    isAbstract: false,
                    isFinal: false,
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
