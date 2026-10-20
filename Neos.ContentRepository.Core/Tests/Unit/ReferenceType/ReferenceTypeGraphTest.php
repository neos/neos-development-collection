<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\ReferenceType;

use Neos\ContentRepository\Core\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\ReferenceType\ReferenceType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeConstraints;
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
        ReferenceTypeConstraints $constraints,
        ReferenceTypeNameSet $expectedResult,
    ): void {
        Assert::assertEquals(
            $expectedResult,
            self::createConstraintCheckSubject()->getReferenceTypesMatchingConstraints($constraints)->toNames(),
        );
    }

    /**
     * @return iterable<string,array{
     *     constraints: ReferenceTypeConstraints,
     *     expectedResult: ReferenceTypeNameSet,
     * }>
     */
    public static function constraintProvider(): iterable
    {
        yield 'unconstrained' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::create(),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
                'Acme.Site:AbstractIntermediary',
                'Acme.Site:Root',
            ]),
        ];

        yield 'forbidden' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: false,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::create(),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::create(),
        ];

        yield 'excludingAbstract' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: false,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::create(),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
                'Acme.Site:Root',
            ]),
        ];

        yield 'everythingAndThenSome' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
                'Acme.Site:AbstractIntermediary',
                'Acme.Site:Root',
            ]),
        ];

        yield 'onlyUndefined' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: false,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Undefined',
                ]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([]),
        ];

        yield 'onlyLeaf' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: false,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
            ]),
        ];

        yield 'onlyIntermediary' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: false,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
                'Acme.Site:AbstractIntermediary',
            ]),
        ];

        yield 'onlyIntermediaryButNoAbstract' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: false,
                includeAbstract: false,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::create(),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
            ]),
        ];

        yield 'everythingButTheLeaf' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:AbstractIntermediary',
                'Acme.Site:Root',
            ]),
        ];

        yield 'everythingButTheLeafButNoAbstract' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: false,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Root',
            ]),
        ];

        yield 'everythingButTheIntermediary' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Root',
            ]),
        ];

        yield 'bothExplicitWithWildcard' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
                'Acme.Site:Root',
            ]),
        ];

        yield 'bothExplicitWithoutWildcard' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: false,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Reference',
                ]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:Reference',
            ]),
        ];

        yield 'conflictingSuperTypesWithWildcard' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:Root',
                ]),
            ),
            'expectedResult' => ReferenceTypeNameSet::fromStringArray([
                'Acme.Site:AbstractIntermediary',
            ]),
        ];

        yield 'conflictingSuperTypesWithoutWildcard' => [
            'constraints' => ReferenceTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
                    'Acme.Site:AbstractIntermediary',
                ]),
                explicitlyDisallowedNodeTypes: ReferenceTypeNameSet::fromStringArray([
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
            isFinal: false,
            sourceNodeTypeConstraints: NodeTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: NodeTypeNames::createEmpty(),
                explicitlyDisallowedNodeTypes: NodeTypeNames::createEmpty()
            ),
            targetNodeTypeConstraints: NodeTypeConstraints::create(
                isWildcardAllowed: true,
                includeAbstract: true,
                explicitlyAllowedNodeTypes: NodeTypeNames::createEmpty(),
                explicitlyDisallowedNodeTypes: NodeTypeNames::createEmpty()
            ),
            properties: PropertyDictionary::create([]),
        );
    }
}
