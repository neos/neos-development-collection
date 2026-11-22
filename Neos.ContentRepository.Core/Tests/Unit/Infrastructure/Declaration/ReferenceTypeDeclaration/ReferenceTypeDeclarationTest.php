<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Infrastructure\Declaration\ReferenceTypeDeclaration;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\Infrastructure\Declaration\Exception\ReferenceTypeDeclarationIsInvalid;
use Neos\ContentRepository\Core\Infrastructure\Declaration\NodeTypeFiltersDeclaration;
use Neos\ContentRepository\Core\Infrastructure\Declaration\PropertyDeclaration;
use Neos\ContentRepository\Core\Infrastructure\Declaration\PropertyDeclarationDictionary;
use Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration\ReferenceTypeConstraintsDeclaration;
use Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration\ReferenceTypeDeclaration;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Property\GenericArrayPropertyMetadata;
use Neos\ContentRepository\Core\SharedModel\Property\PropertyMetadataDictionary;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReferenceTypeDeclarationTest extends TestCase
{
    #[DataProvider('creationParametersProvider')]
    public function testCreate(
        ReferenceTypeName $name,
        bool $isAbstract,
        bool $isFinal,
        ReferenceTypeNameList $superTypeNames,
        ReferenceTypeConstraintsDeclaration $constraints,
        PropertyDeclarationDictionary $properties,
        bool $resultExpected,
        ?\Throwable $expectedException,
    ): void {
        try {
            $actualResult = ReferenceTypeDeclaration::create(
                name: $name,
                isAbstract: $isAbstract,
                isFinal: $isFinal,
                superTypeNames: $superTypeNames,
                constraints: $constraints,
                properties: $properties,
            );
            $actualException = null;
        } catch (\Throwable $actualException) {
            $actualResult = null;
        }

        if ($resultExpected) {
            Assert::assertInstanceOf(ReferenceTypeDeclaration::class, $actualResult);
        } else {
            Assert::assertNull($actualResult);
        }
        Assert::assertEquals($expectedException, $actualException);
    }

    /**
     * @return iterable<string,array{
     *     name: ReferenceTypeName,
     *     isAbstract: bool,
     *     isFinal: bool,
     *     superTypeNames: ReferenceTypeNameList,
     *     constraints: ReferenceTypeConstraintsDeclaration,
     *     properties: PropertyDeclarationDictionary,
     *     resultExpected: bool,
     *     expectedException: ?\Throwable,
     * }>
     */
    public static function creationParametersProvider(): iterable
    {
        yield 'minimal parameters' => [
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'isAbstract' => false,
            'isFinal' => false,
            'superTypeNames' => ReferenceTypeNameList::list(),
            'constraints' => ReferenceTypeConstraintsDeclaration::create(
                sourceNodeTypes: NodeTypeFiltersDeclaration::create(true, []),
                targetNodeTypes: NodeTypeFiltersDeclaration::create(true, []),
            ),
            'properties' => PropertyDeclarationDictionary::create(),
            'resultExpected' => true,
            'expectedException' => null,
        ];

        yield 'maximal parameters' => [
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'isAbstract' => false,
            'isFinal' => true,
            'superTypeNames' => ReferenceTypeNameList::list(
                ReferenceTypeName::fromString('Acme.Site:BaseReference')
            ),
            'constraints' => ReferenceTypeConstraintsDeclaration::create(
                sourceNodeTypes: NodeTypeFiltersDeclaration::create(false, ['Acme.Site:SomeNodeType']),
                targetNodeTypes: NodeTypeFiltersDeclaration::create(false, ['Acme.Site:SomeOtherNodeType']),
            ),
            'properties' => PropertyDeclarationDictionary::create(
                PropertyDeclaration::create(
                    name: PropertyName::fromString('text'),
                    type: PropertyType::int(),
                    scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                    defaultValue: 42,
                    metadata: PropertyMetadataDictionary::create([
                        '_catchAll' => GenericArrayPropertyMetadata::create([
                            'foo' => 'baz',
                            'noodle' => 'soup',
                            'wat' => 'atouille',
                            'bool' => true,
                        ]),
                    ]),
                    declaringType: ReferenceTypeName::fromString('Acme.Site:Reference'),
                )
            ),
            'resultExpected' => true,
            'expectedException' => null,
        ];

        yield 'both abstract and final' => [
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'isAbstract' => true,
            'isFinal' => true,
            'superTypeNames' => ReferenceTypeNameList::list(),
            'constraints' => ReferenceTypeConstraintsDeclaration::create(
                sourceNodeTypes: NodeTypeFiltersDeclaration::create(true, []),
                targetNodeTypes: NodeTypeFiltersDeclaration::create(true, []),
            ),
            'properties' => PropertyDeclarationDictionary::create(),
            'resultExpected' => false,
            'expectedException' => ReferenceTypeDeclarationIsInvalid::becauseItIsBothAbstractAndFinal(
                ReferenceTypeName::fromString('Acme.Site:Reference')
            ),
        ];
    }

    /**
     * @param array<string,mixed> $values
     */
    #[DataProvider('arrayProvider')]
    public function testFromArray(
        array $values,
        string $name,
        ReferenceTypeDeclaration $expectedResult,
    ): void {
        Assert::assertEquals($expectedResult, ReferenceTypeDeclaration::fromArray($values, $name));
    }

    /**
     * @return iterable<string,array{
     *     values: array<string,mixed>,
     *     name: string,
     *     expectedResult: ReferenceTypeDeclaration,
     * }>
     */
    public static function arrayProvider(): iterable
    {
        yield 'minimal values' => [
            'values' => [],
            'name' => 'Acme.Site:Reference',
            'expectedResult' => ReferenceTypeDeclaration::create(
                name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                isAbstract: false,
                isFinal: false,
                superTypeNames: ReferenceTypeNameList::list(),
                constraints: ReferenceTypeConstraintsDeclaration::create(
                    sourceNodeTypes: NodeTypeFiltersDeclaration::create(false, []),
                    targetNodeTypes: NodeTypeFiltersDeclaration::create(false, []),
                ),
                properties: PropertyDeclarationDictionary::create(),
            )
        ];

        yield 'maximal values' => [
            'values' => [
                'abstract' => true,
                'superTypes' => [
                    'Acme.Site:BaseReference' => true,
                ],
                'constraints' => [
                    'sourceNodeTypes' => [
                        '*' => true,
                        'Acme.Site:Node' => false,
                    ],
                    'targetNodeTypes' => [
                        'Acme.Site:Node' => true,
                    ],
                ],
                'properties' => [
                    'text' => [
                        'type' => 'string',
                        'scope' => 'nodeAggregate',
                        'defaultValue' => 'my text',
                        'custom' => [
                            'someKey' => 'someValue'
                        ],
                    ],
                ],
            ],
            'name' => 'Acme.Site:Reference',
            'expectedResult' => ReferenceTypeDeclaration::create(
                name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                isAbstract: true,
                isFinal: false,
                superTypeNames: ReferenceTypeNameList::list(
                    ReferenceTypeName::fromString('Acme.Site:BaseReference'),
                ),
                constraints: ReferenceTypeConstraintsDeclaration::create(
                    sourceNodeTypes: NodeTypeFiltersDeclaration::create(true, ['Acme.Site:Node' => false]),
                    targetNodeTypes: NodeTypeFiltersDeclaration::create(false, ['Acme.Site:Node' => true]),
                ),
                properties: PropertyDeclarationDictionary::create(
                    PropertyDeclaration::create(
                        name: PropertyName::fromString('text'),
                        type: PropertyType::string(),
                        scope: PropertyScope::SCOPE_NODE_AGGREGATE,
                        defaultValue: 'my text',
                        metadata: PropertyMetadataDictionary::create([
                            '_catchAll' => GenericArrayPropertyMetadata::create([
                                'custom' => [
                                    'someKey' => 'someValue'
                                ],
                            ]),
                        ]),
                        declaringType: ReferenceTypeName::fromString('Acme.Site:Reference'),
                    )
                ),
            )
        ];
    }
}
