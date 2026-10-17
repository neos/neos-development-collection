<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\ReferenceType;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\ReferenceType\Exception\ReferenceTypeDeclarationIsInvalid;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeDeclaration;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;
use Neos\ContentRepository\Core\SharedModel\Node\NodeTypeConstraintsDeclaration;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyDeclaration;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyDeclarationDictionary;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyMetadataDictionary;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
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
        NodeTypeConstraintsDeclaration $sourceNodeTypes,
        NodeTypeConstraintsDeclaration $targetNodeTypes,
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
                sourceNodeTypes: $sourceNodeTypes,
                targetNodeTypes: $targetNodeTypes,
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
     *     sourceNodeTypes: NodeTypeConstraintsDeclaration,
     *     targetNodeTypes: NodeTypeConstraintsDeclaration,
     *     properties: PropertyDeclarationDictionary,
     *     expectedResult: ?ReferenceTypeDeclarationProperties,
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
            'sourceNodeTypes' => NodeTypeConstraintsDeclaration::create(true, []),
            'targetNodeTypes' => NodeTypeConstraintsDeclaration::create(true, []),
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
            'sourceNodeTypes' => NodeTypeConstraintsDeclaration::create(false, ['Acme.Site:SomeNodeType']),
            'targetNodeTypes' => NodeTypeConstraintsDeclaration::create(false, ['Acme.Site:SomeOtherNodeType']),
            'properties' => PropertyDeclarationDictionary::create(
                PropertyDeclaration::create(
                    name: PropertyName::fromString('text'),
                    type: PropertyType::string(),
                    metadata: PropertyMetadataDictionary::create([]),
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
            'sourceNodeTypes' => NodeTypeConstraintsDeclaration::create(true, []),
            'targetNodeTypes' => NodeTypeConstraintsDeclaration::create(true, []),
            'properties' => PropertyDeclarationDictionary::create(),
            'resultExpected' => false,
            'expectedException' => ReferenceTypeDeclarationIsInvalid::becauseItIsBothAbstractAndFinal(
                ReferenceTypeName::fromString('Acme.Site:Reference')
            ),
        ];
    }
}
