<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Projection\ContentGraph\Filter\NodePropertyValue;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\AndCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\NegateCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\OrCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\CriteriaParser\ParserException;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\PropertyValueCriteriaParser;
use Neos\Flow\Tests\UnitTestCase;

class PropertyValueCriteriaParserTest extends UnitTestCase
{
    public function validQueries(): \Generator
    {
        yield ['query' => 'prop1 = "foo"', 'expectedResult' => ['type' => 'PropertyValueEquals', 'propertyName' => 'prop1', 'value' => 'foo']];
        yield ['query' => 'prop1=   \'foo\'', 'expectedResult' => ['type' => 'PropertyValueEquals', 'propertyName' => 'prop1', 'value' => 'foo']];
        yield ['query' => 'some_property > "10"', 'expectedResult' => ['type' => 'PropertyValueGreaterThan', 'propertyName' => 'some_property', 'value' => '10']];
        yield ['query' => 'some_property>10', 'expectedResult' => ['type' => 'PropertyValueGreaterThan', 'propertyName' => 'some_property', 'value' => 10]];
        yield ['query' => 'prop <= 123.45', 'expectedResult' => ['type' => 'PropertyValueLessThanOrEqual', 'propertyName' => 'prop', 'value' => 123.45]];
        yield ['query' => 'prop = true', 'expectedResult' => ['type' => 'PropertyValueEquals', 'propertyName' => 'prop', 'value' => true]];
        yield ['query' => 'prop=FALSE', 'expectedResult' => ['type' => 'PropertyValueEquals', 'propertyName' => 'prop', 'value' => false]];

        yield ['query' => 'p ^= "start" AND p $= \'end\'', 'expectedResult' => ['type' => 'AndCriteria', 'criteria1' => ['type' => 'PropertyValueStartsWith', 'propertyName' => 'p', 'value' => 'start'], 'criteria2' => ['type' => 'PropertyValueEndsWith', 'propertyName' => 'p', 'value' => 'end']]];
        yield ['query' => 'p ^= "start" OR p $= \'end\'', 'expectedResult' => ['type' => 'OrCriteria', 'criteria1' => ['type' => 'PropertyValueStartsWith', 'propertyName' => 'p', 'value' => 'start'], 'criteria2' => ['type' => 'PropertyValueEndsWith', 'propertyName' => 'p', 'value' => 'end']]];

        yield ['query' => 'NOT p = "negate"', 'expectedResult' => ['type' => 'NegateCriteria', 'criteria' => ['type' => 'PropertyValueEquals', 'propertyName' => 'p', 'value' => 'negate']]];
        yield ['query' => 'p != "negate"', 'expectedResult' => ['type' => 'NegateCriteria', 'criteria' => ['type' => 'PropertyValueEquals', 'propertyName' => 'p', 'value' => 'negate']]];

        yield ['query' => '(p *= "foo")', 'expectedResult' => ['type' => 'PropertyValueContains', 'propertyName' => 'p', 'value' => 'foo']];
        yield ['query' => '(p1 *= "foo" OR p2 <= "bar") AND p3 >= 123', 'expectedResult' => ['type' => 'AndCriteria', 'criteria1' => ['type' => 'OrCriteria', 'criteria1' => ['type' => 'PropertyValueContains', 'propertyName' => 'p1', 'value' => 'foo'], 'criteria2' => ['type' => 'PropertyValueLessThanOrEqual', 'propertyName' => 'p2', 'value' => 'bar']], 'criteria2' => ['type' => 'PropertyValueGreaterThanOrEqual', 'propertyName' => 'p3', 'value' => 123]]];
        yield ['query' => 'prop1 ^= "foo" AND NOT (prop2 = "bar" OR prop3 = "baz")', 'expectedResult' => ['type' => 'AndCriteria', 'criteria1' => ['type' => 'PropertyValueStartsWith', 'propertyName' => 'prop1', 'value' => 'foo'], 'criteria2' => ['type' => 'NegateCriteria', 'criteria' => ['type' => 'OrCriteria', 'criteria1' => ['type' => 'PropertyValueEquals', 'propertyName' => 'prop2', 'value' => 'bar'], 'criteria2' => ['type' => 'PropertyValueEquals', 'propertyName' => 'prop3', 'value' => 'baz']]]]];
    }

    /**
     * @test
     * @dataProvider validQueries
     */
    public function parseValidQueriesTest(string $query, array $expectedResult): void
    {
        self::assertSame($expectedResult, self::propertyValueCriteriaToArray(PropertyValueCriteriaParser::parse($query)));
    }

    public function invalidQueries(): \Generator
    {
        yield ['query' => '', 'expectedExceptionMessage' => "Query must not be empty"];
        yield ['query' => '     ', 'expectedExceptionMessage' => "Query must not be empty"];
        yield ['query' => "\n\t", 'expectedExceptionMessage' => "Query must not be empty"];
        yield ['query' => 'foo AND (bar = "baz")', 'expectedExceptionMessage' => "Expecting a comparison operator.\nfoo AND (bar = \"baz\")\n----^"];
        yield ['query' => 'property_näme = 123', 'expectedExceptionMessage' => "Unable to parse character\nproperty_näme = 123\n----------^"];
        yield ['query' => 'prop = füü', 'expectedExceptionMessage' => "Unable to parse character\nprop = füü\n--------^"];
        yield ['query' => 'p1 = "foo" AND', 'expectedExceptionMessage' => "Expecting a property name.\np1 = \"foo\" AND\n--------------^"];
        yield ['query' => 'foo >= true', 'expectedExceptionMessage' => "The GREATER_THAN_OR_EQUAL operator does not support values of type bool\nfoo >= true\n-------^"];
        yield ['query' => 'p ^= 123', 'expectedExceptionMessage' => "The STARTS_WITH operator does not support values of type int\np ^= 123\n-----^"];
        yield ['query' => 'p $= false', 'expectedExceptionMessage' => "The ENDS_WITH operator does not support values of type bool\np $= false\n-----^"];
        yield ['query' => 'p = "this is valid" AND p *= 12.34', 'expectedExceptionMessage' => "The CONTAINS operator does not support values of type float\np = \"this is valid\" AND p *= 12.34\n-----------------------------^"];
    }

    /**
     * @test
     * @dataProvider invalidQueries
     */
    public function parseInvalidQueriesTest(string $query, string $expectedExceptionMessage): void
    {
        try {
            PropertyValueCriteriaParser::parse($query);
        } catch (ParserException $exception) {
            self::assertSame($expectedExceptionMessage, $exception->getMessage());
        }
    }

    private static function propertyValueCriteriaToArray(PropertyValueCriteriaInterface $criteria): array
    {
        $type = (new \ReflectionClass($criteria))->getShortName();
        return match ($criteria::class) {
            AndCriteria::class, OrCriteria::class => ['type' => $type, 'criteria1' => self::propertyValueCriteriaToArray($criteria->criteria1), 'criteria2' => self::propertyValueCriteriaToArray($criteria->criteria2)],
            NegateCriteria::class => ['type' => $type, 'criteria' => self::propertyValueCriteriaToArray($criteria->criteria)],
            default => ['type' => $type, ...json_decode(json_encode($criteria), true)],
        };
    }
}
