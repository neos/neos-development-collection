<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Infrastructure\DeclarationBasedReferenceTypes;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedNodeTypes\NodeTypeFiltersDeclaration;
use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception\ReferenceTypeDeclarationIsInvalid;
use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception\SuperTypeConflict;
use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception\SuperTypeConflicts;
use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\ReferenceTypeConstraintsDeclaration;
use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\ReferenceTypeDeclaration;
use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\ReferenceTypeDeclarationDictionary;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyDeclaration;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyDeclarationDictionary;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\NodeType\NodeTypeFilters;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\ReferenceType\ReferenceType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeConstraints;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Property\GenericArrayPropertyMetadata;
use Neos\ContentRepository\Core\SharedModel\Property\Property;
use Neos\ContentRepository\Core\SharedModel\Property\PropertyDictionary;
use Neos\ContentRepository\Core\SharedModel\Property\PropertyMetadataDictionary;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReferenceTypeDeclarationDictionaryTest extends TestCase
{
    #[DataProvider('referenceTypeDeclarationProvider')]
    public function testFromDeclarations(
        ReferenceTypeDeclarationDictionary $subject,
        ?\Throwable $expectedException,
    ): void {
        try {
            $subject->expandToGraph();
            $actualException = null;
        } catch (\Throwable $actualException) {
        }

        Assert::assertEquals($expectedException, $actualException);
    }

    /**
     * @return iterable<string,array{
     *     subject: ReferenceTypeDeclarationDictionary,
     *     expectedException: ?\Throwable,
     * }>
     */
    public static function referenceTypeDeclarationProvider(): iterable
    {
        yield 'empty set' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(),
            'expectedException' => null,
        ];

        yield 'unknown supertype' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:Unknown'),
                    ),
                    constraints: ReferenceTypeConstraintsDeclaration::create(
                        sourceNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                        targetNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                    ),
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
            'subject' => ReferenceTypeDeclarationDictionary::create(
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:FinalReference'),
                    ),
                    constraints: ReferenceTypeConstraintsDeclaration::create(
                        sourceNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                        targetNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                    ),
                    properties: PropertyDeclarationDictionary::create(),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:FinalReference'),
                    isAbstract: false,
                    isFinal: true,
                    superTypeNames: ReferenceTypeNameList::list(),
                    constraints: ReferenceTypeConstraintsDeclaration::create(
                        sourceNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                        targetNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                    ),
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
            'subject' => ReferenceTypeDeclarationDictionary::create(
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:AnotherReference'),
                    ),
                    constraints: ReferenceTypeConstraintsDeclaration::create(
                        sourceNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                        targetNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                    ),
                    properties: PropertyDeclarationDictionary::create(),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:AnotherReference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:Reference'),
                    ),
                    constraints: ReferenceTypeConstraintsDeclaration::create(
                        sourceNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                        targetNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                    ),
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
            'subject' => ReferenceTypeDeclarationDictionary::create(
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:ReferenceA'),
                        ReferenceTypeName::fromString('Acme.Site:ReferenceB'),
                    ),
                    constraints: ReferenceTypeConstraintsDeclaration::create(
                        sourceNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                        targetNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                    ),
                    properties: PropertyDeclarationDictionary::create(),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:ReferenceA'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(),
                    constraints: ReferenceTypeConstraintsDeclaration::create(
                        sourceNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                        targetNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                    ),
                    properties: PropertyDeclarationDictionary::create(
                        PropertyDeclaration::create(
                            name: PropertyName::fromString('text'),
                            type: PropertyType::string(),
                            defaultValue: null,
                            metadata: PropertyMetadataDictionary::create([]),
                            declaringType: ReferenceTypeName::fromString('Acme.Site:ReferenceA'),
                        )
                    ),
                ),
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:ReferenceB'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(),
                    constraints: ReferenceTypeConstraintsDeclaration::create(
                        sourceNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                        targetNodeTypes: NodeTypeFiltersDeclaration::create(includeWildcard: true, filteredNodeTypes: []),
                    ),
                    properties: PropertyDeclarationDictionary::create(
                        PropertyDeclaration::create(
                            name: PropertyName::fromString('text'),
                            type: PropertyType::float(),
                            defaultValue: null,
                            metadata: PropertyMetadataDictionary::create([]),
                            declaringType: ReferenceTypeName::fromString('Acme.Site:ReferenceB'),
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
        ReferenceTypeDeclarationDictionary $subject,
        ReferenceTypeName $name,
        ?ReferenceType $expectedResult,
    ): void {
        Assert::assertEquals(
            $expectedResult,
            $subject->expandToGraph()->getReferenceType($name),
        );
    }

    /**
     * @return iterable<string,array{
     *     subject: ReferenceTypeDeclarationDictionary,
     *     name: ReferenceTypeName,
     *     expectedResult: ?ReferenceType,
     * }>
     */
    public static function referenceTypeProvider(): iterable
    {
        yield 'empty dictionary' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => null,
        ];

        yield 'minimal example' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                ReferenceTypeDeclaration::create(
                    name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    isAbstract: false,
                    isFinal: false,
                    superTypeNames: ReferenceTypeNameList::list(),
                    constraints: ReferenceTypeConstraintsDeclaration::create(
                        sourceNodeTypes: NodeTypeFiltersDeclaration::create(false, ['Acme.Site:Node' => true]),
                        targetNodeTypes: NodeTypeFiltersDeclaration::create(false, ['Acme.Site:Node' => true]),
                    ),
                    properties: PropertyDeclarationDictionary::create(),
                )
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceType::create(
                name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                isAbstract: false,
                constraints: ReferenceTypeConstraints::create(
                    sourceNodeTypes: NodeTypeFilters::create(
                        isWildcardAllowed: false,
                        includeAbstract: true,
                        explicitlyIncludedNodeTypeNames: NodeTypeNames::fromStringArray(['Acme.Site:Node']),
                        explicitlyExcludedNodeTypeNames: NodeTypeNames::createEmpty(),
                    ),
                    targetNodeTypes: NodeTypeFilters::create(
                        isWildcardAllowed: false,
                        includeAbstract: true,
                        explicitlyIncludedNodeTypeNames: NodeTypeNames::fromStringArray(['Acme.Site:Node']),
                        explicitlyExcludedNodeTypeNames: NodeTypeNames::createEmpty(),
                    ),
                ),
                properties: PropertyDictionary::create([]),
            ),
        ];

        yield 'simple inheritance, inverse order' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
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
                constraints: ReferenceTypeConstraints::create(
                    sourceNodeTypes: NodeTypeFilters::create(
                        isWildcardAllowed: false,
                        includeAbstract: true,
                        explicitlyIncludedNodeTypeNames: NodeTypeNames::fromStringArray([
                            'Acme.Site:RestrictedNode',
                            'Acme.Site:Node',
                        ]),
                        explicitlyExcludedNodeTypeNames: NodeTypeNames::fromStringArray([
                            'Acme.Site:AnotherRestrictedNode',
                        ]),
                    ),
                    targetNodeTypes: NodeTypeFilters::create(
                        isWildcardAllowed: false,
                        includeAbstract: true,
                        explicitlyIncludedNodeTypeNames: NodeTypeNames::fromStringArray([
                            'Acme.Site:RestrictedNode',
                            'Acme.Site:Node',
                        ]),
                        explicitlyExcludedNodeTypeNames: NodeTypeNames::fromStringArray([
                            'Acme.Site:AnotherRestrictedNode',
                        ]),
                    ),
                ),
                properties: PropertyDictionary::create([
                    'text' => Property::create(
                        name: PropertyName::fromString('text'),
                        type: PropertyType::string(),
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([
                            '_catchAll' => GenericArrayPropertyMetadata::create([
                                'foo' => 'baz',
                                'noodle' => 'soup',
                                'wat' => 'atouille',
                                'bool' => true,
                            ]),
                        ])
                    ),
                    'abstract' => Property::create(
                        name: PropertyName::fromString('abstract'),
                        type: PropertyType::string(),
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'value' => Property::create(
                        name: PropertyName::fromString('value'),
                        type: PropertyType::float(),
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                ]),
            ),
        ];

        yield 'multi-tier multiple inheritance' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                self::getReferenceDeclaration(
                    ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                        ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                    )
                ),
                self::getBaseReferenceDeclaration(),
                self::getAnotherBaseReferenceDeclaration(),
                self::getIntermediateReferenceDeclaration(),
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceType::create(
                name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                isAbstract: false,
                constraints: ReferenceTypeConstraints::create(
                    sourceNodeTypes: NodeTypeFilters::create(
                        isWildcardAllowed: false,
                        includeAbstract: true,
                        explicitlyIncludedNodeTypeNames: NodeTypeNames::fromStringArray([
                            'Acme.Site:RestrictedNode',
                            'Acme.Site:Node',
                        ]),
                        explicitlyExcludedNodeTypeNames: NodeTypeNames::fromStringArray([
                            'Acme.Site:AnotherRestrictedNode',
                            'Acme.Site:YetAnotherRestrictedNode',
                        ]),
                    ),
                    targetNodeTypes: NodeTypeFilters::create(
                        isWildcardAllowed: false,
                        includeAbstract: true,
                        explicitlyIncludedNodeTypeNames: NodeTypeNames::fromStringArray([
                            'Acme.Site:RestrictedNode',
                            'Acme.Site:Node',
                        ]),
                        explicitlyExcludedNodeTypeNames: NodeTypeNames::fromStringArray([
                            'Acme.Site:AnotherRestrictedNode',
                            'Acme.Site:YetAnotherRestrictedNode',
                        ]),
                    ),
                ),
                properties: PropertyDictionary::create([
                    'text' => Property::create(
                        name: PropertyName::fromString('text'),
                        type: PropertyType::string(),
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([
                            '_catchAll' => GenericArrayPropertyMetadata::create([
                                'foo' => 'baz',
                                'noodle' => 'soup',
                                'wat' => 'atouille',
                                'bool' => false,
                                'number' => 42,
                            ]),
                        ])
                    ),
                    'abstract' => Property::create(
                        name: PropertyName::fromString('abstract'),
                        type: PropertyType::string(),
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'value' => Property::create(
                        name: PropertyName::fromString('value'),
                        type: PropertyType::float(),
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'description' => Property::create(
                        name: PropertyName::fromString('description'),
                        type: PropertyType::string(),
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'author' => Property::create(
                        name: PropertyName::fromString('author'),
                        type: PropertyType::string(),
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                ]),
            ),
        ];
    }

    #[DataProvider('declaredSuperTypeNamesProvider')]
    public function testGetDeclaredSuperTypeNames(
        ReferenceTypeDeclarationDictionary $subject,
        ReferenceTypeName $name,
        ReferenceTypeNameList $expectedResult,
    ): void {
        $referenceTypeGraph = $subject->expandToGraph();
        Assert::assertEquals($expectedResult, $referenceTypeGraph->getDeclaredSuperTypeNames($name));
    }

    /**
     * @return iterable<string,array{
     *     subject: ReferenceTypeDeclarationDictionary,
     *     name: ReferenceTypeName,
     *     expectedResult: ReferenceTypeNameList,
     * }>
     */
    public static function declaredSuperTypeNamesProvider(): iterable
    {
        yield 'empty dictionary' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceTypeNameList::list(),
        ];

        yield 'empty supertypes' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                self::getBaseReferenceDeclaration(),
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:BaseReference'),
            'expectedResult' => ReferenceTypeNameList::list(),
        ];

        yield 'supertypes' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                self::getBaseReferenceDeclaration(),
                self::getIntermediateReferenceDeclaration(),
                self::getAnotherBaseReferenceDeclaration(),
                self::getReferenceDeclaration(
                    ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                        ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                    )
                )
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceTypeNameList::list(
                ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
            ),
        ];
    }

    #[DataProvider('superTypeNamesProvider')]
    public function testGetSuperTypeNames(
        ReferenceTypeDeclarationDictionary $subject,
        ReferenceTypeName $name,
        ReferenceTypeNameList $expectedResult,
    ): void {
        $referenceTypeGraph = $subject->expandToGraph();
        Assert::assertEquals($expectedResult, $referenceTypeGraph->getSuperTypeNames($name));
    }

    /**
     * @return iterable<string,array{
     *     subject: ReferenceTypeDeclarationDictionary,
     *     name: ReferenceTypeName,
     *     expectedResult: ReferenceTypeNameList,
     * }>
     */
    public static function superTypeNamesProvider(): iterable
    {
        yield 'empty dictionary' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceTypeNameList::list(),
        ];

        yield 'empty supertypes' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                self::getBaseReferenceDeclaration(),
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:BaseReference'),
            'expectedResult' => ReferenceTypeNameList::list(),
        ];

        yield 'supertypes' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                self::getBaseReferenceDeclaration(),
                self::getIntermediateReferenceDeclaration(),
                self::getAnotherBaseReferenceDeclaration(),
                self::getReferenceDeclaration(
                    ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                        ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                    )
                )
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceTypeNameList::list(
                ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
            ),
        ];
    }

    #[DataProvider('declaredSubTypeNamesProvider')]
    public function testGetDeclaredSubTypeNames(
        ReferenceTypeDeclarationDictionary $subject,
        ReferenceTypeName $name,
        ReferenceTypeNameList $expectedResult,
    ): void {
        $referenceTypeGraph = $subject->expandToGraph();
        Assert::assertEquals($expectedResult, $referenceTypeGraph->getDeclaredSubTypeNames($name));
    }

    /**
     * @return iterable<string,array{
     *     subject: ReferenceTypeDeclarationDictionary,
     *     name: ReferenceTypeName,
     *     expectedResult: ReferenceTypeNameList,
     * }>
     */
    public static function declaredSubTypeNamesProvider(): iterable
    {
        yield 'empty dictionary' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceTypeNameList::list(),
        ];

        yield 'empty subtypes' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                self::getBaseReferenceDeclaration(),
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:BaseReference'),
            'expectedResult' => ReferenceTypeNameList::list(),
        ];

        yield 'subtypes' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                self::getBaseReferenceDeclaration(),
                self::getIntermediateReferenceDeclaration(),
                self::getAnotherBaseReferenceDeclaration(),
                self::getReferenceDeclaration(
                    ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                        ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                    )
                )
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
            'expectedResult' => ReferenceTypeNameList::list(
                ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
            ),
        ];
    }

    #[DataProvider('subTypeNamesProvider')]
    public function testGetSubTypeNames(
        ReferenceTypeDeclarationDictionary $subject,
        ReferenceTypeName $name,
        ReferenceTypeNameList $expectedResult,
    ): void {
        $referenceTypeGraph = $subject->expandToGraph();
        Assert::assertEquals($expectedResult, $referenceTypeGraph->getSubTypeNames($name));
    }

    /**
     * @return iterable<string,array{
     *     subject: ReferenceTypeDeclarationDictionary,
     *     name: ReferenceTypeName,
     *     expectedResult: ReferenceTypeNameList,
     * }>
     */
    public static function subTypeNamesProvider(): iterable
    {
        yield 'empty dictionary' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(),
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'expectedResult' => ReferenceTypeNameList::list(),
        ];

        yield 'empty subtypes' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                self::getBaseReferenceDeclaration(),
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:BaseReference'),
            'expectedResult' => ReferenceTypeNameList::list(),
        ];

        yield 'subtypes' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                self::getBaseReferenceDeclaration(),
                self::getIntermediateReferenceDeclaration(),
                self::getAnotherBaseReferenceDeclaration(),
                self::getReferenceDeclaration(
                    ReferenceTypeNameList::list(
                        ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                        ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                    )
                )
            ),
            'name' => ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
            'expectedResult' => ReferenceTypeNameList::list(
                ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                ReferenceTypeName::fromString('Acme.Site:Reference'),
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
            constraints: ReferenceTypeConstraintsDeclaration::create(
                sourceNodeTypes: NodeTypeFiltersDeclaration::create(
                    includeWildcard: false,
                    filteredNodeTypes: [
                        'Acme.Site:Node' => true,
                        'Acme.Site:RestrictedNode' => true,
                    ],
                ),
                targetNodeTypes: NodeTypeFiltersDeclaration::create(
                    includeWildcard: false,
                    filteredNodeTypes: [
                        'Acme.Site:Node' => true,
                        'Acme.Site:RestrictedNode' => true,
                    ],
                ),
            ),
            properties: PropertyDeclarationDictionary::create(
                PropertyDeclaration::create(
                    name: PropertyName::fromString('text'),
                    type: PropertyType::string(),
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([
                        '_catchAll' => GenericArrayPropertyMetadata::create([
                            'foo' => 'baz',
                            'wat' => 'atouille',
                        ]),
                    ]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:Reference')
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('value'),
                    type: PropertyType::float(),
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:Reference')
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
            constraints: ReferenceTypeConstraintsDeclaration::create(
                sourceNodeTypes: NodeTypeFiltersDeclaration::create(
                    includeWildcard: true,
                    filteredNodeTypes: [
                        'Acme.Site:RestrictedNode' => false,
                        'Acme.Site:AnotherRestrictedNode' => false,
                    ],
                ),
                targetNodeTypes: NodeTypeFiltersDeclaration::create(
                    includeWildcard: true,
                    filteredNodeTypes: [
                        'Acme.Site:RestrictedNode' => false,
                        'Acme.Site:AnotherRestrictedNode' => false,
                    ],
                ),
            ),
            properties: PropertyDeclarationDictionary::create(
                PropertyDeclaration::create(
                    name: PropertyName::fromString('text'),
                    type: PropertyType::string(),
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([
                        '_catchAll' => GenericArrayPropertyMetadata::create([
                            'foo' => 'bar',
                            'noodle' => 'soup',
                            'bool' => true,
                        ]),
                    ]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('abstract'),
                    type: PropertyType::string(),
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                ),
            ),
        );
    }

    private static function getAnotherBaseReferenceDeclaration(): ReferenceTypeDeclaration
    {
        return ReferenceTypeDeclaration::create(
            name: ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
            isAbstract: true,
            isFinal: false,
            superTypeNames: ReferenceTypeNameList::list(),
            constraints: ReferenceTypeConstraintsDeclaration::create(
                sourceNodeTypes: NodeTypeFiltersDeclaration::create(
                    includeWildcard: true,
                    filteredNodeTypes: [
                        'Acme.Site:RestrictedNode' => false,
                        'Acme.Site:AnotherRestrictedNode' => false,
                        'Acme.Site:YetAnotherRestrictedNode' => false,
                    ],
                ),
                targetNodeTypes: NodeTypeFiltersDeclaration::create(
                    includeWildcard: true,
                    filteredNodeTypes: [
                        'Acme.Site:RestrictedNode' => false,
                        'Acme.Site:AnotherRestrictedNode' => false,
                        'Acme.Site:YetAnotherRestrictedNode' => false,
                    ],
                ),
            ),
            properties: PropertyDeclarationDictionary::create(
                PropertyDeclaration::create(
                    name: PropertyName::fromString('text'),
                    type: PropertyType::string(),
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([
                        '_catchAll' => GenericArrayPropertyMetadata::create([
                            'foo' => 'barrr',
                            'number' => 42,
                        ]),
                    ]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('description'),
                    type: PropertyType::string(),
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
                ),
            ),
        );
    }

    private static function getIntermediateReferenceDeclaration(): ReferenceTypeDeclaration
    {
        return ReferenceTypeDeclaration::create(
            name: ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
            isAbstract: true,
            isFinal: false,
            superTypeNames: ReferenceTypeNameList::list(
                ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
            ),
            constraints: ReferenceTypeConstraintsDeclaration::create(
                sourceNodeTypes: NodeTypeFiltersDeclaration::create(
                    includeWildcard: true,
                    filteredNodeTypes: [],
                ),
                targetNodeTypes: NodeTypeFiltersDeclaration::create(
                    includeWildcard: true,
                    filteredNodeTypes: [],
                ),
            ),
            properties: PropertyDeclarationDictionary::create(
                PropertyDeclaration::create(
                    name: PropertyName::fromString('text'),
                    type: PropertyType::string(),
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([
                        '_catchAll' => GenericArrayPropertyMetadata::create([
                            'foo' => 'intermediate',
                            'bool' => false,
                        ]),
                    ]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('author'),
                    type: PropertyType::string(),
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                ),
            ),
        );
    }
}
