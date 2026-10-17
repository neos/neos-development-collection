<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\ReferenceType;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\ReferenceType\Exception\ReferenceTypeDeclarationIsInvalid;
use Neos\ContentRepository\Core\ReferenceType\ReferenceType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeDeclaration;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeDeclarationDictionary;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;
use Neos\ContentRepository\Core\ReferenceType\Exception\SuperTypeConflict;
use Neos\ContentRepository\Core\ReferenceType\Exception\SuperTypeConflicts;
use Neos\ContentRepository\Core\SharedModel\Node\GenericArrayPropertyMetadata;
use Neos\ContentRepository\Core\SharedModel\Node\NodeTypeConstraintsDeclaration;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyDeclaration;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyDeclarationDictionary;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyMetadataDictionary;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReferenceTypeGraphTest extends TestCase
{
    #[DataProvider('referenceTypeDeclarationProvider')]
    public function testFromDeclarations(
        ReferenceTypeDeclarationDictionary $referenceTypeDeclarations,
        ?\Throwable $expectedException,
    ): void {
        try {
            ReferenceTypeGraph::create($referenceTypeDeclarations);
            $actualException = null;
        } catch (\Throwable $actualException) {
        }

        Assert::assertEquals($expectedException, $actualException);
    }

    /**
     * @return iterable<string,array{
     *     referenceTypeDeclarations: ReferenceTypeDeclarationDictionary,
     *     expectedException: ?\Throwable,
     * }>
     */
    public static function referenceTypeDeclarationProvider(): iterable
    {
        yield 'empty set' => [
            'referenceTypeDeclarations' => ReferenceTypeDeclarationDictionary::create(),
            'expectedException' => null,
        ];

        yield 'unknown supertype' => [
            'referenceTypeDeclarations' => ReferenceTypeDeclarationDictionary::create(
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:Unknown'),
                    ),
                    sourceNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    targetNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    properties: PropertyDeclarationDictionary::create(),
                ),
            ),
            'expectedException' => ReferenceTypeDeclarationIsInvalid::becauseItHasUnknownSuperTypes(
                ReferenceTypeName::fromString('Acme.Site:Reference'),
                ReferenceTypeNameList::list(
                    ReferenceTypeName::fromString('Acme.Site:Unknown'),
                ),
            ),
        ];

        yield 'final supertype' => [
            'referenceTypeDeclarations' => ReferenceTypeDeclarationDictionary::create(
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:FinalReference'),
                    ),
                    sourceNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    targetNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    properties: PropertyDeclarationDictionary::create(),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:FinalReference'),
                    isAbstract: false,
                    isFinal: true,
                    superTypeNames: ReferenceTypeNameList::list(),
                    sourceNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    targetNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    properties: PropertyDeclarationDictionary::create(),
                ),
            ),
            'expectedException' => ReferenceTypeDeclarationIsInvalid::becauseItHasFinalSuperTypes(
                ReferenceTypeName::fromString('Acme.Site:Reference'),
                ReferenceTypeNameList::list(
                    ReferenceTypeName::fromString('Acme.Site:FinalReference'),
                ),
            ),
        ];

        yield 'circular supertypes' => [
            'referenceTypeDeclarations' => ReferenceTypeDeclarationDictionary::create(
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:AnotherReference'),
                    ),
                    sourceNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    targetNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    properties: PropertyDeclarationDictionary::create(),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:AnotherReference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:Reference'),
                    ),
                    sourceNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    targetNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    properties: PropertyDeclarationDictionary::create(),
                ),
            ),
            'expectedException' => ReferenceTypeDeclarationIsInvalid::becauseItHasCircularSuperTypes(
                ReferenceTypeName::fromString('Acme.Site:Reference'),
                ReferenceTypeNameList::list(
                    ReferenceTypeName::fromString('Acme.Site:Reference'),
                    ReferenceTypeName::fromString('Acme.Site:AnotherReference'),
                    ReferenceTypeName::fromString('Acme.Site:Reference'),
                ),
            ),
        ];

        yield 'conflicting supertype' => [
            'referenceTypeDeclarations' => ReferenceTypeDeclarationDictionary::create(
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:ReferenceA'),
                        ReferenceTypeName::fromString('Acme.Site:ReferenceB'),
                    ),
                    sourceNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    targetNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    properties: PropertyDeclarationDictionary::create(),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:ReferenceA'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(),
                    sourceNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    targetNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    properties: PropertyDeclarationDictionary::create(
                        PropertyDeclaration::create(
                            name: PropertyName::fromString('text'),
                            type: PropertyType::string(),
                            metadata: PropertyMetadataDictionary::create([]),
                        )
                    ),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:ReferenceB'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(),
                    sourceNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    targetNodeTypes: NodeTypeConstraintsDeclaration::create(isWildcardAllowed: true, constrainedNodeTypes: []),
                    properties: PropertyDeclarationDictionary::create(
                        PropertyDeclaration::create(
                            name: PropertyName::fromString('text'),
                            type: PropertyType::float(),
                            metadata: PropertyMetadataDictionary::create([]),
                        )
                    ),
                ),
            ),
            'expectedException' => ReferenceTypeDeclarationIsInvalid::becauseItHasConflictingSuperTypes(
                ReferenceTypeName::fromString('Acme.Site:Reference'),
                SuperTypeConflicts::create([
                    'text' => [
                        SuperTypeConflict::create(
                            referenceTypeName: ReferenceTypeName::fromString('Acme.Site:ReferenceA'),
                            propertyType: PropertyType::string(),
                        ),
                        SuperTypeConflict::create(
                            referenceTypeName: ReferenceTypeName::fromString('Acme.Site:ReferenceB'),
                            propertyType: PropertyType::float(),
                        )
                    ]
                ]),
            ),
        ];
    }

    #[DataProvider('referenceTypeProvider')]
    public function testGetReferenceType(
        ReferenceTypeDeclarationDictionary $referenceTypeDeclarations,
        ReferenceTypeName $name,
        ?ReferenceType $expectedResult,
    ): void {
        Assert::assertEquals(
            $expectedResult,
            ReferenceTypeGraph::create($referenceTypeDeclarations)->getReferenceType($name),
        );
    }

    /**
     * @return iterable<string,array{
     *     referenceTypeDeclarations: ReferenceTypeDeclarationDictionary,
     *     name: ReferenceTypeName,
     *     expectedResult: ?ReferenceType,
     * }>
     */
    public static function referenceTypeProvider(): iterable
    {
        yield 'empty dictionary' => [
            'referenceTypeDeclarations' => ReferenceTypeDeclarationDictionary::create(),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => null,
        ];

        yield 'minimal example' => [
            'referenceTypeDeclarations' => ReferenceTypeDeclarationDictionary::create(
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(),
                    sourceNodeTypes: NodeTypeConstraintsDeclaration::create(false, ['Acme.Site:Node' => true]),
                    targetNodeTypes: NodeTypeConstraintsDeclaration::create(false, ['Acme.Site:Node' => true]),
                    properties: PropertyDeclarationDictionary::create(),
                )
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceType::create(
                name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                isAbstract: false,
                isFinal: false,
                sourceNodeTypes: NodeTypeConstraintsDeclaration::create(false, ['Acme.Site:Node' => true]),
                targetNodeTypes: NodeTypeConstraintsDeclaration::create(false, ['Acme.Site:Node' => true]),
                properties: PropertyDeclarationDictionary::create(),
            ),
        ];

        yield 'simple inheritance, inverse order' => [
            'referenceTypeDeclarations' => ReferenceTypeDeclarationDictionary::create(
                self::getReferenceDeclaration(
                    ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                    )
                ),
                self::getBaseReferenceDeclaration(),
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceType::create(
                name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                isAbstract: false,
                isFinal: true,
                sourceNodeTypes: NodeTypeConstraintsDeclaration::create(
                    isWildcardAllowed: false,
                    constrainedNodeTypes: [
                        'Acme.Site:Node' => true,
                        'Acme.Site:RestrictedNode' => true,
                        'Acme.Site:AnotherRestrictedNode' => false,
                    ],
                ),
                targetNodeTypes: NodeTypeConstraintsDeclaration::create(
                    isWildcardAllowed: false,
                    constrainedNodeTypes: [
                        'Acme.Site:Node' => true,
                        'Acme.Site:RestrictedNode' => true,
                        'Acme.Site:AnotherRestrictedNode' => false,
                    ],
                ),
                properties: PropertyDeclarationDictionary::create(
                    PropertyDeclaration::create(
                        name: PropertyName::fromString('text'),
                        type: PropertyType::string(),
                        metadata: PropertyMetadataDictionary::create([
                            'generic' => GenericArrayPropertyMetadata::create([
                                'foo' => 'baz',
                                'noodle' => 'soup',
                                'wat' => 'atouille',
                            ]),
                        ])
                    ),
                    PropertyDeclaration::create(
                        name: PropertyName::fromString('abstract'),
                        type: PropertyType::string(),
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    PropertyDeclaration::create(
                        name: PropertyName::fromString('value'),
                        type: PropertyType::float(),
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                ),
            ),
        ];

        yield 'multi-tier multiple inheritance' => [
            'referenceTypeDeclarations' => ReferenceTypeDeclarationDictionary::create(
                self::getReferenceDeclaration(
                    ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                    )
                ),
                self::getBaseReferenceDeclaration(),
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceType::create(
                name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                isAbstract: false,
                isFinal: true,
                sourceNodeTypes: NodeTypeConstraintsDeclaration::create(
                    isWildcardAllowed: false,
                    constrainedNodeTypes: [
                        'Acme.Site:Node' => true,
                        'Acme.Site:RestrictedNode' => true,
                        'Acme.Site:AnotherRestrictedNode' => false,
                    ],
                ),
                targetNodeTypes: NodeTypeConstraintsDeclaration::create(
                    isWildcardAllowed: false,
                    constrainedNodeTypes: [
                        'Acme.Site:Node' => true,
                        'Acme.Site:RestrictedNode' => true,
                        'Acme.Site:AnotherRestrictedNode' => false,
                    ],
                ),
                properties: PropertyDeclarationDictionary::create(
                    PropertyDeclaration::create(
                        name: PropertyName::fromString('text'),
                        type: PropertyType::string(),
                        metadata: PropertyMetadataDictionary::create([
                            'generic' => GenericArrayPropertyMetadata::create([
                                'foo' => 'baz',
                                'noodle' => 'soup',
                                'wat' => 'atouille',
                            ]),
                        ])
                    ),
                    PropertyDeclaration::create(
                        name: PropertyName::fromString('abstract'),
                        type: PropertyType::string(),
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    PropertyDeclaration::create(
                        name: PropertyName::fromString('value'),
                        type: PropertyType::float(),
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                ),
            ),
        ];
    }

    private static function getReferenceDeclaration(ReferenceTypeNameList $superTypeNames): ReferenceTypeDeclaration
    {
        return ReferenceTypeDeclaration::create(
            name: ReferenceTypeName::fromString('Acme.Site:Reference'),
            isAbstract: false,
            isFinal: true,
            superTypeNames: $superTypeNames,
            sourceNodeTypes: NodeTypeConstraintsDeclaration::create(
                isWildcardAllowed: false,
                constrainedNodeTypes: [
                    'Acme.Site:Node' => true,
                    'Acme.Site:RestrictedNode' => true,
                ],
            ),
            targetNodeTypes: NodeTypeConstraintsDeclaration::create(
                isWildcardAllowed: false,
                constrainedNodeTypes: [
                    'Acme.Site:Node' => true,
                    'Acme.Site:RestrictedNode' => true,
                ],
            ),
            properties: PropertyDeclarationDictionary::create(
                PropertyDeclaration::create(
                    name: PropertyName::fromString('text'),
                    type: PropertyType::string(),
                    metadata: PropertyMetadataDictionary::create([
                        'generic' => GenericArrayPropertyMetadata::create([
                            'foo' => 'baz',
                            'wat' => 'atouille',
                        ]),
                    ])
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('value'),
                    type: PropertyType::float(),
                    metadata: PropertyMetadataDictionary::create([]),
                ),
            ),
        );
    }

    private static function getBaseReferenceDeclaration(): ReferenceTypeDeclaration
    {
        return ReferenceTypeDeclaration::create(
            name: ReferenceTypeName::fromString('Acme.Site:BaseReference'),
            isAbstract: true,
            isFinal: false,
            superTypeNames: ReferenceTypeNameList::list(),
            sourceNodeTypes: NodeTypeConstraintsDeclaration::create(
                isWildcardAllowed: true,
                constrainedNodeTypes: [
                    'Acme.Site:RestrictedNode' => false,
                    'Acme.Site:AnotherRestrictedNode' => false,
                ],
            ),
            targetNodeTypes: NodeTypeConstraintsDeclaration::create(
                isWildcardAllowed: true,
                constrainedNodeTypes: [
                    'Acme.Site:RestrictedNode' => false,
                    'Acme.Site:AnotherRestrictedNode' => false,
                ],
            ),
            properties: PropertyDeclarationDictionary::create(
                PropertyDeclaration::create(
                    name: PropertyName::fromString('text'),
                    type: PropertyType::string(),
                    metadata: PropertyMetadataDictionary::create([
                        'generic' => GenericArrayPropertyMetadata::create([
                            'foo' => 'bar',
                            'noodle' => 'soup',
                        ]),
                    ])
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('abstract'),
                    type: PropertyType::string(),
                    metadata: PropertyMetadataDictionary::create([]),
                ),
            ),
        );
    }
}
