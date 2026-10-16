<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\ReferenceType;

use Neos\ContentRepository\Core\ReferenceType\Exception\ReferenceTypeDeclarationIsInvalid;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeDeclaration;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;
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
        ?ReferenceTypeDeclarationProperties $expectedResult,
        ?\Throwable $expectedException,
    ): void {
        try {
            $actualResult = ReferenceTypeDeclaration::create($name, $isAbstract, $isFinal, $superTypeNames);
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
     *     name: ReferenceTypeName,
     *     isAbstract: bool,
     *     isFinal: bool,
     *     superTypeNames: ReferenceTypeNameList,
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
            'expectedResult' => new ReferenceTypeDeclarationProperties(
                name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                isAbstract: false,
                isFinal: false,
                superTypeNames: ReferenceTypeNameList::list(),
            ),
            'expectedException' => null,
        ];

        yield 'maximal parameters' => [
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'isAbstract' => false,
            'isFinal' => true,
            'superTypeNames' => ReferenceTypeNameList::list(
                ReferenceTypeName::fromString('Acme.Site:BaseReference')
            ),
            'expectedResult' => new ReferenceTypeDeclarationProperties(
                name: ReferenceTypeName::fromString('Acme.Site:Reference'),
                isAbstract: false,
                isFinal: true,
                superTypeNames: ReferenceTypeNameList::list(
                    ReferenceTypeName::fromString('Acme.Site:BaseReference')
                ),
            ),
            'expectedException' => null,
        ];

        yield 'both abstract and final' => [
            'name' => ReferenceTypeName::fromString('Acme.Site:Reference'),
            'isAbstract' => true,
            'isFinal' => true,
            'superTypeNames' => ReferenceTypeNameList::list(),
            'expectedResult' => null,
            'expectedException' => ReferenceTypeDeclarationIsInvalid::becauseItIsBothAbstractAndFinal(
                ReferenceTypeName::fromString('Acme.Site:Reference')
            ),
        ];
    }
}
