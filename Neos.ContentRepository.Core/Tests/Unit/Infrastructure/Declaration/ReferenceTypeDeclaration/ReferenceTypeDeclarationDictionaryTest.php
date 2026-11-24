<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Infrastructure\Declaration\ReferenceTypesDeclaration;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\Infrastructure\Declaration\Exception\ReferenceTypeDeclarationIsInvalid;
use Neos\ContentRepository\Core\Infrastructure\Declaration\Exception\SuperTypeConflict;
use Neos\ContentRepository\Core\Infrastructure\Declaration\Exception\SuperTypeConflicts;
use Neos\ContentRepository\Core\Infrastructure\Declaration\NodeTypeFiltersDeclaration;
use Neos\ContentRepository\Core\Infrastructure\Declaration\PropertyDeclaration;
use Neos\ContentRepository\Core\Infrastructure\Declaration\PropertyDeclarationDictionary;
use Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration\ReferenceTypeConstraintsDeclaration;
use Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration\ReferenceTypeDeclaration;
use Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration\ReferenceTypeDeclarationDictionary;
use Neos\ContentRepository\Core\Infrastructure\Declaration\Undeclared;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ArrayNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\CollectionTypeDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ScalarNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\UriNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectArrayDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectBoolDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectFloatDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectIntDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectStringDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\NodeType\NodeTypeFilters;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\ReferenceType\ReferenceType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeConstraints;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeFilters;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameSet;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Property\GenericArrayPropertyMetadata;
use Neos\ContentRepository\Core\SharedModel\Property\Property;
use Neos\ContentRepository\Core\SharedModel\Property\PropertyDictionary;
use Neos\ContentRepository\Core\SharedModel\Property\PropertyMetadataDictionary;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

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
        $propertyConverter = self::createPropertyConverter();

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
                    propertyConverter: $propertyConverter,
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
                    propertyConverter: $propertyConverter,
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
                    propertyConverter: $propertyConverter,
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
                    propertyConverter: $propertyConverter,
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
                    propertyConverter: $propertyConverter,
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
                    propertyConverter: $propertyConverter,
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
                            scope: Undeclared::INSTANCE,
                            defaultValue: null,
                            metadata: PropertyMetadataDictionary::create([]),
                            declaringType: ReferenceTypeName::fromString('Acme.Site:ReferenceA'),
                            propertyConverter: $propertyConverter,
                        )
                    ),
                    propertyConverter: $propertyConverter,
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
                            scope: Undeclared::INSTANCE,
                            defaultValue: null,
                            metadata: PropertyMetadataDictionary::create([]),
                            declaringType: ReferenceTypeName::fromString('Acme.Site:ReferenceB'),
                            propertyConverter: $propertyConverter,
                        )
                    ),
                    propertyConverter: $propertyConverter,
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
                    propertyConverter: self::createPropertyConverter(),
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
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
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
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                        defaultValue: 'base abstract',
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'value' => Property::create(
                        name: PropertyName::fromString('value'),
                        type: PropertyType::float(),
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                ]),
            ),
        ];

        yield 'multi-tier multiple inheritance' => [
            'subject' => ReferenceTypeDeclarationDictionary::create(
                self::getReferenceDeclaration(
                    superTypeNames: ReferenceTypeNameList::list(
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
                    'initiallyUndeclaredAndModified' => Property::create(
                        name: PropertyName::fromString('initiallyUndeclaredAndModified'),
                        type: PropertyType::int(),
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                        defaultValue: 43,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'initiallyUndeclaredAndUnmodified' => Property::create(
                        name: PropertyName::fromString('initiallyUndeclaredAndUnmodified'),
                        type: PropertyType::string(),
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'initiallyDeclaredAndUnmodified' => Property::create(
                        name: PropertyName::fromString('initiallyDeclaredAndUnmodified'),
                        type: PropertyType::int(),
                        scope: PropertyScope::SCOPE_SPECIALIZATIONS,
                        defaultValue: 42,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'initiallyDeclaredAndModified' => Property::create(
                        name: PropertyName::fromString('initiallyDeclaredAndModified'),
                        type: PropertyType::int(),
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                        defaultValue: 43,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'text' => Property::create(
                        name: PropertyName::fromString('text'),
                        type: PropertyType::string(),
                        // undeclared from self
                        // undeclared from intermediate
                        // specializations from another base which is the last applied with a scope
                        scope: PropertyScope::SCOPE_SPECIALIZATIONS,
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
                    // undeclared in self
                    // undeclared in intermediate
                    // undeclared in another base
                    // declared in base as
                    // * scope node aggregate
                    // * default value "base abstract"
                    'abstract' => Property::create(
                        name: PropertyName::fromString('abstract'),
                        type: PropertyType::string(),
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                        defaultValue: 'base abstract',
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'value' => Property::create(
                        name: PropertyName::fromString('value'),
                        type: PropertyType::float(),
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'description' => Property::create(
                        name: PropertyName::fromString('description'),
                        type: PropertyType::string(),
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                        defaultValue: null,
                        metadata: PropertyMetadataDictionary::create([]),
                    ),
                    'author' => Property::create(
                        name: PropertyName::fromString('author'),
                        type: PropertyType::string(),
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
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
        $actualResult = $referenceTypeGraph->getSuperTypeNames($name);
        Assert::assertEquals(
            $expectedResult,
            $actualResult,
            'Expected ' . \json_encode($expectedResult->toStringArray())
                . ', got' . \json_encode($actualResult->toStringArray())
        );
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
        $propertyConverter = self::createPropertyConverter();

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
                    scope: Undeclared::INSTANCE,
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([
                        '_catchAll' => GenericArrayPropertyMetadata::create([
                            'foo' => 'baz',
                            'wat' => 'atouille',
                        ]),
                    ]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    propertyConverter: $propertyConverter,
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('value'),
                    type: PropertyType::float(),
                    scope: Undeclared::INSTANCE,
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    propertyConverter: $propertyConverter,
                ),
            ),
            propertyConverter: $propertyConverter,
        );
    }

    private static function getBaseReferenceDeclaration(): ReferenceTypeDeclaration
    {
        $propertyConverter = self::createPropertyConverter();

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
                    scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                    defaultValue: 'base text',
                    metadata: PropertyMetadataDictionary::create([
                        '_catchAll' => GenericArrayPropertyMetadata::create([
                            'foo' => 'bar',
                            'noodle' => 'soup',
                            'bool' => true,
                        ]),
                    ]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                    propertyConverter: $propertyConverter,
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('abstract'),
                    type: PropertyType::string(),
                    scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                    defaultValue: 'base abstract',
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                    propertyConverter: $propertyConverter,
                ),
            ),
            propertyConverter: $propertyConverter,
        );
    }

    private static function getAnotherBaseReferenceDeclaration(): ReferenceTypeDeclaration
    {
        $propertyConverter = self::createPropertyConverter();

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
                    name: PropertyName::fromString('initiallyUndeclaredAndModified'),
                    type: Undeclared::INSTANCE,
                    scope: Undeclared::INSTANCE,
                    defaultValue: Undeclared::INSTANCE,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
                    propertyConverter: $propertyConverter,
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('initiallyUndeclaredAndUnmodified'),
                    type: Undeclared::INSTANCE,
                    scope: Undeclared::INSTANCE,
                    defaultValue: Undeclared::INSTANCE,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
                    propertyConverter: $propertyConverter,
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('initiallyDeclaredAndUnmodified'),
                    type: PropertyType::int(),
                    scope: PropertyScope::SCOPE_SPECIALIZATIONS,
                    defaultValue: 42,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
                    propertyConverter: $propertyConverter,
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('initiallyDeclaredAndModified'),
                    type: PropertyType::int(),
                    scope: PropertyScope::SCOPE_SPECIALIZATIONS,
                    defaultValue: 42,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
                    propertyConverter: $propertyConverter,
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('text'),
                    type: PropertyType::string(),
                    scope: PropertyScope::SCOPE_SPECIALIZATIONS,
                    defaultValue: 'another text',
                    metadata: PropertyMetadataDictionary::create([
                        '_catchAll' => GenericArrayPropertyMetadata::create([
                            'foo' => 'barrr',
                            'number' => 42,
                        ]),
                    ]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
                    propertyConverter: $propertyConverter,
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('description'),
                    type: PropertyType::string(),
                    scope: Undeclared::INSTANCE,
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:AnotherBaseReference'),
                    propertyConverter: $propertyConverter,
                ),
            ),
            propertyConverter: $propertyConverter,
        );
    }

    private static function getIntermediateReferenceDeclaration(): ReferenceTypeDeclaration
    {
        $propertyConverter = self::createPropertyConverter();

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
                    name: PropertyName::fromString('initiallyUndeclaredAndModified'),
                    type: PropertyType::int(),
                    scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                    defaultValue: 43,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                    propertyConverter: $propertyConverter,
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('initiallyDeclaredAndModified'),
                    type: Undeclared::INSTANCE,
                    scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                    defaultValue: 43,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                    propertyConverter: $propertyConverter,
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('text'),
                    type: PropertyType::string(),
                    scope: Undeclared::INSTANCE,
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([
                        '_catchAll' => GenericArrayPropertyMetadata::create([
                            'foo' => 'intermediate',
                            'bool' => false,
                        ]),
                    ]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                    propertyConverter: $propertyConverter,
                ),
                PropertyDeclaration::create(
                    name: PropertyName::fromString('author'),
                    type: PropertyType::string(),
                    scope: Undeclared::INSTANCE,
                    defaultValue: null,
                    metadata: PropertyMetadataDictionary::create([]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:IntermediateReference'),
                    propertyConverter: $propertyConverter,
                ),
            ),
            propertyConverter: $propertyConverter,
        );
    }

    #[DataProvider('constraintProvider')]
    public function testGetReferenceTypesMatchingConstraints(
        ReferenceTypeFilters $constraints,
        ReferenceTypeNameSet $expectedResult,
    ): void {
        Assert::assertEquals(
            $expectedResult,
            self::createConstraintCheckSubject()->expandToGraph()->getReferenceTypesMatchingConstraints($constraints)->toNames(),
        );
    }

    /**
     * @return iterable<string,array{
     *     constraints: ReferenceTypeFilters,
     *     expectedResult: ReferenceTypeNameSet,
     * }>
     */
    public static function constraintProvider(): iterable
    {
        yield 'unconstrained' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: true,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::create(),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
                'Acme.Site:AbstractIntermediary',
                'Acme.Site:Root',
            ]),
        ];

        yield 'forbidden' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: false,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::create(),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::create(),
        ];

        yield 'excludingAbstract' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: true,
                includeAbstract: false,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::create(),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
                'Acme.Site:Root',
            ]),
        ];

        yield 'everythingAndThenSome' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: true,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
                'Acme.Site:AbstractIntermediary',
                'Acme.Site:Root',
            ]),
        ];

        yield 'onlyUndefined' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: false,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Undefined',
                ]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([]),
        ];

        yield 'onlyLeaf' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: false,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
            ]),
        ];

        yield 'onlyIntermediary' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: false,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
                'Acme.Site:AbstractIntermediary',
            ]),
        ];

        yield 'onlyIntermediaryButNoAbstract' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: false,
                includeAbstract: false,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
            ]),
        ];

        yield 'everythingButTheLeaf' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: true,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:AbstractIntermediary',
                'Acme.Site:Root',
            ]),
        ];

        yield 'everythingButTheLeafButNoAbstract' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: true,
                includeAbstract: false,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Root',
            ]),
        ];

        yield 'everythingButTheIntermediary' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: true,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Root',
            ]),
        ];

        yield 'bothExplicitWithWildcard' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: true,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
                'Acme.Site:Root',
            ]),
        ];

        yield 'bothExplicitWithoutWildcard' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: false,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
            ]),
        ];

        yield 'conflictingSuperTypesWithWildcard' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: true,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Root',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:AbstractIntermediary',
                'Acme.Site:Reference',
            ]),
        ];

        yield 'conflictingSuperTypesWithoutWildcard' => [
            'constraints' => ReferenceTypeFilters::create(
                includeWildcard: false,
                includeAbstract: true,
                explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
                explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Root',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:AbstractIntermediary',
                'Acme.Site:Reference',
            ]),
        ];
    }

    private static function createConstraintCheckSubject(): ReferenceTypeDeclarationDictionary
    {
        return ReferenceTypeDeclarationDictionary::create(
            self::createConstraintCheckSubjectReferenceType(
                name: 'Acme.Site:Reference',
                isAbstract: false,
                superTypeNames: ReferenceTypeNameList::list(
                    ReferenceTypeName::fromString('Acme.Site:AbstractIntermediary'),
                )
            ),
            self::createConstraintCheckSubjectReferenceType(
                name: 'Acme.Site:AbstractIntermediary',
                isAbstract: true,
                superTypeNames: ReferenceTypeNameList::list(
                    ReferenceTypeName::fromString('Acme.Site:Root'),
                )
            ),
            self::createConstraintCheckSubjectReferenceType(
                name: 'Acme.Site:Root',
                isAbstract: false,
                superTypeNames: ReferenceTypeNameList::list()
            ),
        );
    }

    private static function createConstraintCheckSubjectReferenceType(
        string $name,
        bool $isAbstract,
        ReferenceTypeNameList $superTypeNames,
    ): ReferenceTypeDeclaration {
        return ReferenceTypeDeclaration::create(
            name: ReferenceTypeName::fromString($name),
            isAbstract: $isAbstract,
            isFinal: false,
            superTypeNames: $superTypeNames,
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
            properties: PropertyDeclarationDictionary::create(),
            propertyConverter: self::createPropertyConverter(),
        );
    }

    private static function createPropertyConverter(): PropertyConverter
    {
        return new PropertyConverter(
            new Serializer([
                new DateTimeNormalizer(),
                new ScalarNormalizer(),
                new BackedEnumNormalizer(),
                new ArrayNormalizer(),
                new UriNormalizer(),
                new ValueObjectArrayDenormalizer(),
                new ValueObjectBoolDenormalizer(),
                new ValueObjectFloatDenormalizer(),
                new ValueObjectIntDenormalizer(),
                new ValueObjectStringDenormalizer(),
                new CollectionTypeDenormalizer()
            ])
        );
    }
}
