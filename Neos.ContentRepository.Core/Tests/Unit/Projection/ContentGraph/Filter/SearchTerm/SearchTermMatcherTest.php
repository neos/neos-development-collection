<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Projection\ContentGraph\Filter\SearchTerm;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\SearchTerm\SearchTerm;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\SearchTerm\SearchTermMatcher;
use PHPUnit\Framework\TestCase;

class SearchTermMatcherTest extends TestCase
{
    public static function matchingStringComparisonExamples(): iterable
    {
        yield 'string found inside string' => ['brown', self::value('the brown fox')];
        yield 'string found inside string (ci)' => ['BrOWn', self::value('the brown fox')];

        yield 'string found inside multibyte string (ci)' => ['Äpfel', self::value('Auer schreit der bauer die äpfel sind zu sauer.')];

        yield 'string matches full string' => ['Sheep', self::value('Sheep')];
        yield 'string matches full string (ci)' => ['sheep', self::value('Sheep')];

        yield 'string found inside string with special chars' => ['ä b-c+#@', self::value('the example: "ä b-c+#@"')];
    }

    public static function matchingNumberLikeComparisonExamples(): iterable
    {
        yield 'string-number found inside string' => [
            '22',
            self::value('feeling like 22 ;)'),
        ];

        yield 'string-number found inside string-number' => [
            '00',
            self::value('007'),
        ];

        yield 'string-number found inside int' => [
            '23',
            self::value(1234),
        ];

        yield 'string-number found inside float' => [
            '23',
            self::value(1234.56),
        ];

        yield 'string-float matches float' => [
            '1234.56',
            self::value(1234.56),
        ];

        yield 'string-int matches int' => [
            '0',
            self::value(0),
        ];
    }

    public static function matchingBooleanLikeComparisonExamples(): iterable
    {
        yield 'string-boolean inside string' => [
            'true',
            self::value('this is true'),
        ];

        yield 'string-true matches boolean' => [
            'true',
            self::value(true),
        ];

        yield 'string-false matches boolean' => [
            'false',
            self::value(false),
        ];

        yield 'string part matches boolean' => [
            'ru',
            self::value(true),
        ];
    }

    public static function matchingArrayComparisonExamples(): iterable
    {
        // automates the following:
        yield 'inside array: string found inside string' => ['foo', self::value(['foo'])];
        yield 'inside array-object: string found inside string' => ['foo', self::value(new \ArrayObject(['foo']))];

        foreach([
            ...iterator_to_array(self::matchingStringComparisonExamples()),
            ...iterator_to_array(self::matchingNumberLikeComparisonExamples()),
            ...iterator_to_array(self::matchingBooleanLikeComparisonExamples()),
        ] as $name => [$searchTerm, $properties]) {
            /** @var SerializedPropertyValues $properties */
            yield 'inside nested array: ' . $name => [$searchTerm, SerializedPropertyValues::fromArray(
                array_map(
                    fn (SerializedPropertyValue $value) => SerializedPropertyValue::create(
                        // arbitrary deep nested
                        [[$value->value]],
                        'array'
                    ),
                    iterator_to_array($properties)
                )
            )];
        }
    }

    public function emptySearchTermAlwaysMatches(): iterable
    {
        yield '1 property' => ['', self::value('foo')];
        yield '1 empty property' => ['', self::value('foo')];
        yield '0 properties' => ['', SerializedPropertyValues::fromArray([])];
    }

    public function notMatchingExamples(): iterable
    {
        yield 'different chars' => ['aepfel', self::value('äpfel')];
        yield 'upper boolean string representation' => ['TRUE', self::value(true)];
        yield 'string not found inside string' => ['reptv', self::value('eras tour')];
        yield 'integer' => ['0999', self::value(999)];
        yield 'float with comma' => ['12,45', self::value(12.34)];
        yield 'array with unmatched string' => ['hello', self::value(['hi'])];
        yield 'array key is not considered matching' => ['key', self::value(['key' => 'foo'])];
        yield 'nested array key is not considered matching' => ['key', self::value([['key' => 'foo']])];
    }

    /**
     * @test
     * @dataProvider matchingStringComparisonExamples
     * @dataProvider matchingNumberLikeComparisonExamples
     * @dataProvider matchingBooleanLikeComparisonExamples
     * @dataProvider matchingArrayComparisonExamples
     * @dataProvider emptySearchTermAlwaysMatches
     */
    public function searchTermMatchesProperties(
        string $searchTerm,
        SerializedPropertyValues $properties,
    ) {
        self::assertTrue(
            SearchTermMatcher::matchesSerializedPropertyValues(
                $properties,
                SearchTerm::fulltext($searchTerm)
            )
        );
    }

    /**
     * @test
     * @dataProvider notMatchingExamples
     */
    public function searchTermDoesntMatchesProperties(
        string $searchTerm,
        SerializedPropertyValues $properties,
    ) {
        self::assertFalse(
            SearchTermMatcher::matchesSerializedPropertyValues(
                $properties,
                SearchTerm::fulltext($searchTerm)
            )
        );
    }

    private static function value(string|bool|float|int|array|\ArrayObject $value): SerializedPropertyValues
    {
        return SerializedPropertyValues::fromArray([
            'test-property' => SerializedPropertyValue::create(
                $value,
                ''
            ),
        ]);
    }
}
