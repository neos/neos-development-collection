<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\ReferenceType;

use Neos\ContentRepository\Core\ReferenceType\Exception\ReferenceTypeDeclarationIsInvalid;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeDeclaration;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeDeclarationDictionary;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;
use Neos\ContentRepository\Core\SharedModel\Node\NodeTypeConstraintsDeclaration;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyDeclarationDictionary;
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

        yield 'circular supertype' => [
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
    }
}
