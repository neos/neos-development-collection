<?php
namespace Neos\ContentRepository\NodeAccess\Tests\Unit\Filter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
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
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\NodeAccess\Filter\NodeFilterCriteria;
use Neos\ContentRepository\NodeAccess\Filter\NodeFilterCriteriaGroup;
use Neos\ContentRepository\NodeAccess\Filter\NodeFilterCriteriaGroupFactory;
use Neos\Eel\FlowQuery\FizzleParser;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Abstract base class for the Query Operation tests
 */
class NodeFilterCriteriaGroupFactoryTest extends UnitTestCase
{

    public function nodeFilterCriteriaGroupFactoryDataProvider(): \Generator
    {
        yield 'single InstanceOf' => ['[instanceof Foo:Bar]', [["types" => "Foo:Bar", "properties" => ""]]];
        yield 'single NotInstanceOf' => ['[!instanceof Foo:Bar]', [["types" => "!Foo:Bar", "properties" => ""]]];
        yield 'instanceOf OR combination' => ['[instanceof Foo:Bar],[instanceof Foo:Baz]', [["types" => "Foo:Bar", "properties" => ""], ["types" => "Foo:Baz", "properties" => ""]]];
        yield 'instanceOf AND combination' => ['[instanceof Foo:Bar][instanceof Foo:Baz]', [["types" => "Foo:Bar,Foo:Baz", "properties" => ""]]];
        yield 'notInstanceOf OR combination' => ['[instanceof Foo:Bar],[!instanceof Foo:Baz]', [["types" => "Foo:Bar", "properties" => ""], ["types" => "!Foo:Baz", "properties" => ""]]];
        yield 'notInstanceOf AND combination' => ['[instanceof Foo:Bar][!instanceof Foo:Baz]', [["types" => "Foo:Bar,!Foo:Baz", "properties" => ""]]];

        yield 'single PropertyEquals filter (case sensitive)' => ['[foo="bar"]', [["types" => "", "properties" => "foo=bar"]]];
        yield 'single PropertyEquals filter (case insensitive)' => ['[foo=~"bar"]', [["types" => "", "properties" => "foo=~bar"]]];
        yield 'single PropertyNotEquals filter (case sensitive)' => ['[foo!="bar"]', [["types" => "", "properties" => "!(foo=bar)"]]];
        yield 'single PropertyNotEquals filter (case insensitive)' => ['[foo!=~"bar"]', [["types" => "", "properties" => "!(foo=~bar)"]]];
        yield 'single PropertyContains filter (case sensitive)' => ['[foo*="bar"]', [["types" => "", "properties" => "foo*=bar"]]];
        yield 'single PropertyContains filter (case insensitive)' => ['[foo*=~"bar"]', [["types" => "", "properties" => "foo*=~bar"]]];
        yield 'single PropertyStartsWith filter (case sensitive)' => ['[foo^="bar"]', [["types" => "", "properties" => "foo^=bar"]]];
        yield 'single PropertyStartsWith filter (case insensitive)' => ['[foo^=~"bar"]', [["types" => "", "properties" => "foo^=~bar"]]];
        yield 'single PropertyEndsWith filter (case sensitive)' => ['[foo$="bar"]', [["types" => "", "properties" => "foo$=bar"]]];
        yield 'single PropertyEndsWith filter (case insensitive)' => ['[foo$=~"bar"]', [["types" => "", "properties" => "foo$=~bar"]]];

        yield 'single PropertyGreaterThan filter' => ['[foo > 123]', [["types" => "", "properties" => "foo>123"]]];
        yield 'single PropertyGreaterThanOrEqual filter' => ['[foo >= 123]', [["types" => "", "properties" => "foo>=123"]]];
        yield 'single PropertyLessThan filter ' => ['[foo < 123]', [["types" => "", "properties" => "foo<123"]]];
        yield 'single PropertyLessThanOrEqual filter' => ['[foo <= 123]', [["types" => "", "properties" => "foo<=123"]]];

        yield 'multiple Property AND filter' => ['[foo="bar"][bar="baz"]', [["types" => "", "properties" => "(foo=bar&&bar=baz)"]]];
        yield 'multiple Property OR filter' => ['[foo="bar"],[bar="baz"]', [["types" => "", "properties" => "foo=bar"], ["types" => "", "properties" => "bar=baz"]]];

        yield 'combination of Property AND NodeTypeFilter' => ['[instanceof Foo:Bar][bar="baz"]', [["types" => "Foo:Bar", "properties" => "bar=baz"]]];
        yield 'combination of Property OR NodeTypeFilter' => ['[instanceof Foo:Bar],[bar="baz"]', [["types" => "Foo:Bar", "properties" => ""], ["types" => "", "properties" => "bar=baz"]]];
    }

    /**
     * @dataProvider nodeFilterCriteriaGroupFactoryDataProvider
     */
    public function testNodeFilterCriteriaGroupFactory(string $fizzleExpresssion, array $expectation): void
    {
        $nodeFilterCriteria = NodeFilterCriteriaGroupFactory::createFromFizzleExpressionString($fizzleExpresssion);
        $this->assertInstanceOf(NodeFilterCriteriaGroup::class, $nodeFilterCriteria);
        $this->assertSame($expectation, self::nodeFilterCriteriaGroupToArray($nodeFilterCriteria));
    }

    public function nodeFilterCriteriaGroupIsNotCreatedForUnknownFiltersDataProvider(): \Generator
    {
        yield 'absolute node path' => ['/<Neos.Neos:Sites>/foo/bar'];
        yield 'relative node path' => ['foo/bar/baz'];
        yield 'node id' => ['#4d39e8b8-cd05-49ca-bd64-5efc4ea176e9'];

        yield 'mixture of valid and invalid parts' => ['[instanceof Foo:Bar],/<Neos.Neos:Sites>/foo/baz'];
        yield 'multiple pathes' => ['foo/bar/baz, bar/baz/bam'];
        yield 'multiple ids' => ['#4d39e8b8-cd05-49ca-bd64-5efc4ea176e9,#4d39e8b8-cd05-49ca-bd64-5efc4ea17619'];
    }

    /**
     * @dataProvider nodeFilterCriteriaGroupIsNotCreatedForUnknownFiltersDataProvider
     */
    public function testNodeFilterCriteriaGroupIsNotCreatedForUnknownFilters(string $fizzleExpresssion): void
    {
        $nodeFilterCriteria = NodeFilterCriteriaGroupFactory::createFromFizzleExpressionString($fizzleExpresssion);
        $this->assertNull( $nodeFilterCriteria);
    }

    private static function nodeFilterCriteriaGroupToArray(NodeFilterCriteriaGroup $criteriaGroup): array
    {
        return array_map(
            fn(NodeFilterCriteria $criteria) => self::nodeFilterCriteriaToArray($criteria),
            iterator_to_array($criteriaGroup->getIterator())
        );
    }

    private static function nodeFilterCriteriaToArray(NodeFilterCriteria $criteria): array
    {
        return [
            'types' => $criteria->nodeTypeCriteria ? self::nodeTypeCriteriaToString( $criteria->nodeTypeCriteria) : '',
            'properties' => $criteria->propertyValueCriteria ? self::propertyValueCriteriaToString($criteria->propertyValueCriteria): ''
        ];
    }

    private static function nodeTypeCriteriaToString(NodeTypeCriteria $criteria): string
    {
        $resultParts = [];
        foreach($criteria->explicitlyAllowedNodeTypeNames as $allowedNodeTypeName) {
            $resultParts[] = $allowedNodeTypeName->value;
        }
        foreach($criteria->explicitlyDisallowedNodeTypeNames as $disallowedNodeTypeName) {
            $resultParts[] = '!' . $disallowedNodeTypeName->value;
        }
        return implode(',', $resultParts);
    }

    private static function propertyValueCriteriaToString(PropertyValueCriteriaInterface $criteria): string
    {
        return match ($criteria::class) {
            AndCriteria::class => '(' . self::propertyValueCriteriaToString($criteria->criteria1)  . '&&' .  self::propertyValueCriteriaToString($criteria->criteria2) . ')',
            OrCriteria::class => '(' . self::propertyValueCriteriaToString($criteria->criteria1)  . '||' .  self::propertyValueCriteriaToString($criteria->criteria2) . ')',
            NegateCriteria::class => '!(' . self::propertyValueCriteriaToString($criteria->criteria) . ')',
            PropertyValueStartsWith::class => $criteria->propertyName->value . '^=' . ($criteria->caseSensitive ? '' : '~') . $criteria->value,
            PropertyValueEndsWith::class => $criteria->propertyName->value . '$=' . ($criteria->caseSensitive ? '' : '~') . $criteria->value,
            PropertyValueContains::class => $criteria->propertyName->value . '*=' . ($criteria->caseSensitive ? '' : '~') . $criteria->value,
            PropertyValueEquals::class => $criteria->propertyName->value . '=' . ($criteria->caseSensitive ? '' : '~') . $criteria->value,
            PropertyValueGreaterThan::class => $criteria->propertyName->value . '>' . $criteria->value,
            PropertyValueLessThan::class => $criteria->propertyName->value . '<' . $criteria->value,
            PropertyValueGreaterThanOrEqual::class => $criteria->propertyName->value . '>=' . $criteria->value,
            PropertyValueLessThanOrEqual::class => $criteria->propertyName->value . '<=' . $criteria->value,
            default => throw new \InvalidArgumentException('type ' . $criteria::class . ' was not hancled'())
        };
    }
}
