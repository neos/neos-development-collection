<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\ReferenceType;

use Neos\ContentRepository\Core\NodeType\NodeTypeFilters;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\ReferenceType\ReferenceType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeConstraints;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeFilters;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameSet;
use Neos\ContentRepository\Core\SharedModel\Property\PropertyDictionary;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReferenceTypeGraphTest extends TestCase
{
    #[DataProvider('constraintProvider')]
    public function testGetReferenceTypesMatchingConstraints(
        ReferenceTypeFilters $constraints,
        ReferenceTypeNameSet $expectedResult,
    ): void {
        Assert::assertEquals(
            $expectedResult,
            self::createConstraintCheckSubject()->getReferenceTypesMatchingConstraints($constraints)->toNames(),
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
            ]),
        ];

        yield 'conflictingSuperTypesWithoutWildcard' => [
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
            ]),
        ];
    }

    private static function createConstraintCheckSubject(): ReferenceTypeGraph
    {
        return ReferenceTypeGraph::create(
            declaredSuperTypeNames: [
                'Acme.Site:Reference' => ReferenceTypeNameList::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
                'Acme.Site:AbstractIntermediary' => ReferenceTypeNameList::fromStringArray([
                    'Acme.Site:Root',
                ]),
            ],
            superTypeNames: [
                'Acme.Site:Reference' => ReferenceTypeNameList::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                    'Acme.Site:Root',
                ]),
                'Acme.Site:AbstractIntermediary' => ReferenceTypeNameList::fromStringArray([
                    'Acme.Site:Root',
                ]),
            ],
            declaredSubTypesNames: [
                'Acme.Site:AbstractIntermediary' => ReferenceTypeNameList::fromStringArray([
                    'Acme.Site:Reference',
                ]),
                'Acme.Site:Root' => ReferenceTypeNameList::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
            ],
            subTypeNames: [
                'Acme.Site:Root' => ReferenceTypeNameList::fromStringArray([
                    'Acme.Site:Reference',
                ]),
                'Acme.Site:AbstractIntermediary' => ReferenceTypeNameList::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                    'Acme.Site:Reference',
                ]),
            ],
            referenceTypes: self::createConstraintCheckSubjectReferenceTypes([
                'Acme.Site:Reference' => false,
                'Acme.Site:AbstractIntermediary' => true,
                'Acme.Site:Root' => false,
            ]),
        );
    }

    /**
     * @param array<string,bool> $parameters
     * @return array<string,ReferenceType>
     */
    private static function createConstraintCheckSubjectReferenceTypes(array $parameters): array
    {
        $referenceTypes = [];
        foreach ($parameters as $name => $isAbstract) {
            $referenceTypes[$name] = self::createConstraintCheckSubjectReferenceType($name, $isAbstract);
        }

        return $referenceTypes;
    }

    private static function createConstraintCheckSubjectReferenceType(string $name, bool $isAbstract): ReferenceType
    {
        return ReferenceType::create(
            name: ReferenceTypeName::fromString($name),
            isAbstract: $isAbstract,
            constraints: ReferenceTypeConstraints::create(
                sourceNodeTypes: NodeTypeFilters::create(
                    isWildcardAllowed: true,
                    includeAbstract: true,
                    explicitlyIncludedNodeTypeNames: NodeTypeNames::createEmpty(),
                    explicitlyExcludedNodeTypeNames: NodeTypeNames::createEmpty()
                ),
                targetNodeTypes: NodeTypeFilters::create(
                    isWildcardAllowed: true,
                    includeAbstract: true,
                    explicitlyIncludedNodeTypeNames: NodeTypeNames::createEmpty(),
                    explicitlyExcludedNodeTypeNames: NodeTypeNames::createEmpty()
                ),
            ),
            properties: PropertyDictionary::create([]),
        );
    }
}
