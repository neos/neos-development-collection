<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Projection\ContentGraph\PropertyValue;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\AndCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\NegateCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\OrCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueContains;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEndsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueStartsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\PropertyValueCriteriaMatcher;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\TestSuite\Unit\NodeSubjectProvider;
use PHPUnit\Framework\TestCase;

class PropertyValueCriteriaMatcherTest extends TestCase
{
    protected PropertyCollection $propertyCollection;

    public function setUp(): void
    {
        $subjectProvider = new NodeSubjectProvider();
        $this->propertyCollection = new PropertyCollection(
            SerializedPropertyValues::fromArray([
                'nullProperty' => new SerializedPropertyValue(null, 'null'),
                'stringProperty' => new SerializedPropertyValue('foo', 'string'),
                'integerProperty' => new SerializedPropertyValue(123, 'int')
            ]),
            $subjectProvider->propertyConverter
        );
    }

    public function andCriteriaDataProvider(): \Generator
    {
        $trueCriterium = PropertyValueEquals::create(PropertyName::fromString('stringProperty'), 'foo', true);
        $falseCriterium = PropertyValueEquals::create(PropertyName::fromString('stringProperty'), 'other', true);

        yield 'both criteria are true' => [$trueCriterium, $trueCriterium, true];
        yield 'first criterium is true' => [$trueCriterium, $falseCriterium, false];
        yield 'last criterium is true' => [$falseCriterium, $trueCriterium, false];
        yield 'both criteria are false' => [$falseCriterium, $falseCriterium, false];
    }

    /**
     * @test
     * @dataProvider andCriteriaDataProvider
     */
    public function andCriteria(PropertyValueCriteriaInterface $criteriaA, PropertyValueCriteriaInterface $criteriaB, $expectation)
    {

        $this->assertSame(
            $expectation,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                AndCriteria::create($criteriaA, $criteriaB)
            )
        );
    }

    public function negateCriteriaDataProvider(): \Generator
    {
        $trueCriterium = PropertyValueEquals::create(PropertyName::fromString('stringProperty'), 'foo',true);
        $falseCriterium = PropertyValueEquals::create(PropertyName::fromString('stringProperty'), 'other', true);

        yield 'criterium is true' => [$trueCriterium, false];
        yield 'criterium is false' => [$falseCriterium, true];
    }

    /**
     * @test
     * @dataProvider negateCriteriaDataProvider
     */
    public function negateCriteria(PropertyValueCriteriaInterface $criteriaA, $expectation)
    {
        $this->assertSame(
            $expectation,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                NegateCriteria::create($criteriaA)
            )
        );
    }

    public function orCriteriaDataProvider(): \Generator
    {
        $trueCriterium = PropertyValueEquals::create(PropertyName::fromString('stringProperty'), 'foo', true);
        $falseCriterium = PropertyValueEquals::create(PropertyName::fromString('stringProperty'), 'other', true);

        yield 'both criteria are true' => [$trueCriterium, $trueCriterium, true];
        yield 'first criterium is true' => [$trueCriterium, $falseCriterium, true];
        yield 'last criterium is true' => [$falseCriterium, $trueCriterium, true];
        yield 'both criteria are false' => [$falseCriterium, $falseCriterium, false];
    }

    /**
     * @test
     * @dataProvider orCriteriaDataProvider
     */
    public function orCriteria(PropertyValueCriteriaInterface $criteriaA, PropertyValueCriteriaInterface $criteriaB, $expectation)
    {

        $this->assertSame(
            $expectation,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                OrCriteria::create($criteriaA, $criteriaB)
            )
        );
    }

    public function containsCriteriaDataProvider(): \Generator
    {
        yield 'existing "stringProperty" contains "foo"' => ['stringProperty', 'foo', true, true];
        yield 'existing "stringProperty" contains "Foo" (non case sensitive)' => ['stringProperty', 'Foo', false, true];
        yield 'existing "stringProperty" contains "Foo" (case sensitive)' => ['stringProperty', 'Foo', true, false];
        yield 'existing "stringProperty" contains "fo"' => ['stringProperty', 'fo', true, true];
        yield 'existing "stringProperty" contains "oo"' => ['stringProperty', 'oo', true, true];
        yield 'existing "stringProperty" does not contain "bar"' => ['foo', 'bar', true, false];
        yield 'existing "integerProperty" does not contain "foo"' => ['integerProperty', 'foo', true, false];
        yield 'not existing "otherProperty" does not contain "foo"' => ['otherProperty', 'foo', true, false];
    }

    /**
     * @test
     * @dataProvider containsCriteriaDataProvider
     */
    public function containsCriteria(string $propertyName, mixed $propertyValueToExpect, bool $caseSensitive, bool $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                PropertyValueContains::create(
                    PropertyName::fromString($propertyName),
                    $propertyValueToExpect,
                    $caseSensitive
                )
            )
        );
    }

    public function valueStartsWithCriteriaDataProvider(): \Generator
    {
        yield 'existing "stringProperty" starts with "f"' => ['stringProperty', 'f', true, true];
        yield 'existing "stringProperty" starts with "foo"' => ['stringProperty', 'foo', true, true];
        yield 'existing "stringProperty" starts with "Foo" (case insensitive)' => ['stringProperty', 'Foo', false, true];
        yield 'existing "stringProperty" does not start with "Foo" (case sensitive)' => ['stringProperty', 'Foo', true, false];
        yield 'existing "stringProperty" does not start with "fooo"' => ['stringProperty', 'ffoo', true, false];
        yield 'existing "stringProperty" does not start with "bar"' => ['stringProperty', 'bar', true, false];
        yield 'existing "integerProperty" does not start with "foo"' => ['integerProperty', 'foo', true, false];
        yield 'not existing "otherProperty" does not start with "foo"' => ['otherProperty', 'foo', true, false];
    }

    /**
     * @test
     * @dataProvider valueStartsWithCriteriaDataProvider
     */
    public function valueStartsWithCriteria(string $propertyName, mixed $propertyValueToExpect, bool $caseSensitive, bool $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                PropertyValueStartsWith::create(
                    PropertyName::fromString($propertyName),
                    $propertyValueToExpect,
                    $caseSensitive
                )
            )
        );
    }

    public function valueEndsWithCriteriaDataProvider(): \Generator
    {
        yield 'existing "stringProperty" ends with "o"' => ['stringProperty', 'o', true, true];
        yield 'existing "stringProperty" ends with "foo"' => ['stringProperty', 'foo', true, true];
        yield 'existing "stringProperty" ends with "Foo" (case insensitive)' => ['stringProperty', 'Foo', false, true];
        yield 'existing "stringProperty" does not end with "Foo" (case sensitive)' => ['stringProperty', 'Foo', true, false];
        yield 'existing "stringProperty" does not end with "ffoo"' => ['stringProperty', 'ffoo', true, false];
        yield 'existing "stringProperty" does not end with "bar"' => ['stringProperty', 'bar', true, false];
        yield 'existing "integerProperty" does not end with "foo"' => ['integerProperty', 'foo', true, false];
        yield 'not existing "otherProperty" does not end with "foo"' => ['otherProperty', 'foo', true, false];
    }

    /**
     * @test
     * @dataProvider valueEndsWithCriteriaDataProvider
     */
    public function valueEndsWithCriteria(string $propertyName, mixed $propertyValueToExpect, bool $caseSensitive, bool $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                PropertyValueEndsWith::create(
                    PropertyName::fromString($propertyName),
                    $propertyValueToExpect,
                    $caseSensitive
                )
            )
        );
    }

    public function equalsCriteriaDataProvider(): \Generator
    {
        yield 'existing "stringProperty" equals "foo"' => ['stringProperty', 'foo', true, true];
        yield 'existing "stringProperty" equals "Foo" (case insensitive)' => ['stringProperty', 'Foo', false, true];
        yield 'existing "stringProperty" does not equal "Foo" (case sensitive)' => ['stringProperty', 'Foo', true, false];
        yield 'existing "integerProperty" does equal 123' => ['integerProperty', 123, true, true];
        yield 'existing "stringProperty" does not equal "bar"' => ['stringProperty', 'bar', true, false];
        yield 'existing "stringProperty" does not equal 123' => ['stringProperty', 123, true, false];
        yield 'existing "nullProperty" does not equal 123' => ['nullProperty', 123, true, false];
        yield 'existing "stringProperty" does not equal empty string' => ['stringProperty', '', true, false];
        yield 'existing "integerProperty" does not equal 0' => ['integerProperty', 0, true, false];
        yield 'non existing "otherProperty" bar does not equal "foo"' => ['otherProperty', 'foo', true, false];
    }

    /**
     * @test
     * @dataProvider equalsCriteriaDataProvider
     */
    public function equalsCriteria(string $propertyName, mixed $propertyValueToExpect, bool $caseSensitive, bool $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                PropertyValueEquals::create(
                    PropertyName::fromString($propertyName),
                    $propertyValueToExpect,
                    $caseSensitive
                )
            )
        );
    }

    public function greaterThanCriteriaDataProvider(): \Generator
    {
        yield 'existing "integerProperty" is greater than 0' => ['integerProperty', 0, true];
        yield 'existing "integerProperty" is greater than 122' => ['integerProperty', 122, true];
        yield 'existing "integerProperty" is not greater than 123' => ['integerProperty', 123, false];
        yield 'existing "integerProperty" is not greater than 124' => ['integerProperty', 124, false];
        yield 'existing "integerProperty" is not greater than 999' => ['integerProperty', 999, false];
    }

    /**
     * @test
     * @dataProvider greaterThanCriteriaDataProvider
     */
    public function greaterThanCriteria(string $propertyName, mixed $propertyValueToExpect, bool $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                PropertyValueGreaterThan::create(
                    PropertyName::fromString($propertyName),
                    $propertyValueToExpect
                )
            )
        );
    }

    public function greaterThanOrEqualCriteriaDataProvider(): \Generator
    {
        yield 'existing "integerProperty" is greater than 0' => ['integerProperty', 0, true];
        yield 'existing "integerProperty" is greater than 122' => ['integerProperty', 122, true];
        yield 'existing "integerProperty" is not greater than 123' => ['integerProperty', 123, true];
        yield 'existing "integerProperty" is not greater than 124' => ['integerProperty', 124, false];
        yield 'existing "integerProperty" is not greater than 999' => ['integerProperty', 999, false];
    }

    /**
     * @test
     * @dataProvider greaterThanOrEqualCriteriaDataProvider
     */
    public function greaterThanOrEquelCriteria(string $propertyName, mixed $propertyValueToExpect, bool $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                PropertyValueGreaterThanOrEqual::create(
                    PropertyName::fromString($propertyName),
                    $propertyValueToExpect
                )
            )
        );
    }

    public function lessThanCriteriaDataProvider(): \Generator
    {
        yield 'existing "integerProperty" is greater than 0' => ['integerProperty', 0, false];
        yield 'existing "integerProperty" is greater than 122' => ['integerProperty', 122, false];
        yield 'existing "integerProperty" is not greater than 123' => ['integerProperty', 123, false];
        yield 'existing "integerProperty" is not greater than 124' => ['integerProperty', 124, true];
        yield 'existing "integerProperty" is not greater than 999' => ['integerProperty', 999, true];
    }

    /**
     * @test
     * @dataProvider lessThanCriteriaDataProvider
     */
    public function lessThanCriteria(string $propertyName, mixed $propertyValueToExpect, bool $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                PropertyValueLessThan::create(
                    PropertyName::fromString($propertyName),
                    $propertyValueToExpect
                )
            )
        );
    }

    public function lessThanOrEqualCriteriaDataProvider(): \Generator
    {
        yield 'existing "integerProperty" is greater than 0' => ['integerProperty', 0, false];
        yield 'existing "integerProperty" is greater than 122' => ['integerProperty', 122, false];
        yield 'existing "integerProperty" is not greater than 123' => ['integerProperty', 123, true];
        yield 'existing "integerProperty" is not greater than 124' => ['integerProperty', 124, true];
        yield 'existing "integerProperty" is not greater than 999' => ['integerProperty', 999, true];
    }

    /**
     * @test
     * @dataProvider lessThanOrEqualCriteriaDataProvider
     */
    public function lessThanOrEqualCriteria(string $propertyName, mixed $propertyValueToExpect, bool $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            PropertyValueCriteriaMatcher::matchesPropertyCollection(
                $this->propertyCollection,
                PropertyValueLessThanOrEqual::create(
                    PropertyName::fromString($propertyName),
                    $propertyValueToExpect
                )
            )
        );
    }
}
