<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\NodeType;

use Neos\ContentRepository\Core\ReferenceType\Exception\ReferenceTypeNameIsInvalid;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReferenceTypeNameTest extends TestCase
{
    public function testFlyweight(): void
    {
        $referenceTypeName = ReferenceTypeName::fromString('Acme.Site:Reference');
        $objectId = \spl_object_id($referenceTypeName);
        unset($referenceTypeName);
        // just to move the object id counter
        $otherReferenceTypeName = ReferenceTypeName::fromString('Acme.Site:OtherReference');
        $sameReferenceTypeName = ReferenceTypeName::fromString('Acme.Site:Reference');
        Assert::assertNotSame($objectId, \spl_object_id($sameReferenceTypeName));
        $yetAnotherReferenceTypeName = ReferenceTypeName::fromString('Acme.Site:Reference');
        Assert::assertSame($sameReferenceTypeName, $yetAnotherReferenceTypeName);
    }

    #[DataProvider('stringProvider')]
    public function testFromString(
        string $string,
        ?ReferenceTypeName $expectedResult,
        ?\Throwable $expectedException,
    ): void {
        try {
            $actualResult = ReferenceTypeName::fromString($string);
            $actualException = null;
        } catch (\Throwable $actualException) {
            $actualResult = null;
        }

        Assert::assertSame($expectedResult, $actualResult);
        Assert::assertEquals($expectedException, $actualException);
    }

    /**
     * @return iterable<string,array{
     *     string: string,
     *     expectedResult: ?ReferenceTypeName,
     *     expectedException: ?\Throwable,
     * }>
     */
    public static function stringProvider(): iterable
    {
        yield 'empty string' => [
            'string' => '',
            'expectedResult' => null,
            'expectedException' => ReferenceTypeNameIsInvalid::becauseItDoesNotMatchTheRequiredPattern(''),
        ];

        yield 'string with invalid characters' => [
            'string' => 'Acme.Site:$whatever',
            'expectedResult' => null,
            'expectedException' => ReferenceTypeNameIsInvalid::becauseItDoesNotMatchTheRequiredPattern('Acme.Site:$whatever'),
        ];

        yield 'string starting with a number' => [
            'string' => '4tl.Site:Document',
            'expectedResult' => null,
            'expectedException' => ReferenceTypeNameIsInvalid::becauseItDoesNotMatchTheRequiredPattern('4tl.Site:Document'),
        ];

        $tooLongString = \str_repeat('A', 256);
        yield 'too long string' => [
            'string' => $tooLongString,
            'expectedResult' => null,
            'expectedException' => ReferenceTypeNameIsInvalid::becauseItDoesNotMatchTheRequiredPattern($tooLongString),
        ];

        yield 'minimal valid reference type name' => [
            'string' => 'A',
            'expectedResult' => ReferenceTypeName::fromString('A'),
            'expectedException' => null,
        ];

        $longValidString = 'A:.1' . \str_repeat('F', 251);
        yield 'maximal valid reference type name' => [
            'string' => $longValidString,
            'expectedResult' => ReferenceTypeName::fromString($longValidString),
            'expectedException' => null,
        ];
    }
}
