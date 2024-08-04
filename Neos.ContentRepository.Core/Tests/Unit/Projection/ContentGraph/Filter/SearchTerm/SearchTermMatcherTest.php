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
    public function matchingStringComparisonExamples(): iterable
    {
        yield 'string found inside string' => ['brown', self::value('the brown fox')];
        yield 'string found inside string (ci)' => ['BrOWn', self::value('the brown fox')];

        yield 'string found inside multibyte string (ci)' => ['Äpfel', self::value('Auer schreit der bauer die äpfel sind zu sauer.')];

        yield 'string matches full string' => ['Sheep', self::value('Sheep')];
        yield 'string matches full string (ci)' => ['sheep', self::value('Sheep')];

        yield 'string found inside string with special chars' => ['ä b-c+#@', self::value('the example: "ä b-c+#@"')];
    }

    public function matchingNumberLikeComparisonExamples(): iterable
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

    public function matchingBooleanLikeComparisonExamples(): iterable
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
    }

    public function notMatchingExamples(): iterable
    {
        yield 'different chars' => ['aepfel', self::value('äpfel')];
        yield 'upper boolean string representation' => ['TRUE', self::value(true)];
        yield 'string not found inside string' => ['reptv', self::value('eras tour')];
        yield 'integer' => ['0999', self::value(999)];
        yield 'float with comma' => ['12,45', self::value(12.34)];
    }

    /**
     * @test
     * @dataProvider matchingStringComparisonExamples
     * @dataProvider matchingNumberLikeComparisonExamples
     * @dataProvider matchingBooleanLikeComparisonExamples
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

    private static function value(string|bool|float|int $value): SerializedPropertyValues
    {
        return SerializedPropertyValues::fromArray([
            'test-property' => SerializedPropertyValue::create(
                $value,
                gettype($value)
            ),
        ]);
    }
}
