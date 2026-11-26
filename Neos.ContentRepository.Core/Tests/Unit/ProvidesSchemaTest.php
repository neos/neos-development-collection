<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\JsonSchema\ProvidesSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit test cases for the JSON schemas of the value objects that implement {@see ProvidesSchema}
 */
class ProvidesSchemaTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<ProvidesSchema>}>
     */
    public static function schemaProviders(): iterable
    {
        foreach ([...array_keys(self::stringValueObjects()), ...array_keys(self::collections()), ...array_keys(self::enums())] as $class) {
            yield self::shortName($class) => [$class];
        }
        yield 'DimensionSpacePoint' => [DimensionSpacePoint::class];
        yield 'OriginDimensionSpacePoint' => [OriginDimensionSpacePoint::class];
    }

    /**
     * String value objects with values that are accepted and rejected by both, the schema and the `fromString()` constructor
     *
     * @return array<class-string<ProvidesSchema>, array{valid: list<string>, invalid: list<string>}>
     */
    private static function stringValueObjects(): array
    {
        return [
            ContentRepositoryId::class => [
                'valid' => ['default', 'some_cr', 'abcdefghijklmno'],
                'invalid' => ['', 'a', 'Default', '1default', 'default_', 'some-cr', 'abcdefghijklmnop'],
            ],
            ContentStreamId::class => [
                'valid' => ['a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d', '1', str_repeat('a', 40)],
                'invalid' => ['', 'Abc', 'a_b', 'a b', str_repeat('a', 41)],
            ],
            NodeAggregateId::class => [
                'valid' => ['sites', '123', 'a-b-c', str_repeat('a', 64)],
                'invalid' => ['', 'Sites', 'a_b', 'a/b', str_repeat('a', 65)],
            ],
            NodeName::class => [
                // note: NodeName::fromString() lower-cases its input, so upper case values are not part of the invalid ones
                'valid' => ['main', 'my-document', '123'],
                'invalid' => ['', 'my_document', 'my document', 'a/b', 'ä'],
            ],
            NodePath::class => [
                // note: NodePath::fromString() strips leading slashes, so "/foo" is not part of the invalid values
                'valid' => ['', 'main', 'my-document/main'],
                'invalid' => ['foo//bar', 'foo/', 'foo bar', 'foo/b_r'],
            ],
            NodeTypeName::class => [
                'valid' => ['Neos.Neos:Document', 'Vendor.Site:Content.Text', 'x'],
                'invalid' => [''],
            ],
            PropertyName::class => [
                'valid' => ['title', '_hidden', 'some property'],
                'invalid' => [''],
            ],
            ReferenceName::class => [
                'valid' => ['relatedArticles', '_x'],
                'invalid' => [''],
            ],
            SubtreeTag::class => [
                'valid' => ['disabled', 'access-restricted', 'a_b.c', 'e5', '-', '.', '2281f529-d769-4084-9bdb-ea0f89356667', str_repeat('a', 36)],
                'invalid' => ['', 'Disabled', 'a:b', 'invälid', '123', '007', '-1', '1.5', '1.', '.5', '1e5', '1e-5', str_repeat('a', 37)],
            ],
            WorkspaceName::class => [
                'valid' => ['live', 'user-john-doe', '1', str_repeat('a', WorkspaceName::MAX_LENGTH)],
                'invalid' => ['', '-live', 'Live', 'user_john', str_repeat('a', WorkspaceName::MAX_LENGTH + 1)],
            ],
        ];
    }

    /**
     * Collections with a valid instance and a serialized value with an invalid item
     *
     * @return array<class-string<ProvidesSchema>, array{instance: \Closure(): mixed, invalid: list<mixed>}>
     */
    private static function collections(): array
    {
        return [
            ContentRepositoryIds::class => [
                'instance' => static fn () => ContentRepositoryIds::fromArray(['default', 'other']),
                'invalid' => ['default', 'Invalid'],
            ],
            DimensionSpacePointSet::class => [
                'instance' => static fn () => DimensionSpacePointSet::fromArray([['language' => 'de'], ['language' => 'en']]),
                'invalid' => [['language' => 'de'], ['language' => '']],
            ],
            NodeAggregateIds::class => [
                // note: NodeAggregateIds are serialized as object, indexed by their value (see NodeAggregateIdsTest::jsonRepresentation())
                'instance' => static fn () => NodeAggregateIds::fromArray(['abc', 'def', '123']),
                'invalid' => ['abc' => 'abc', 'Invalid' => 'Invalid'],
            ],
            NodeTypeNames::class => [
                // note: NodeTypeNames are not JSON serializable, the schema describes the result of NodeTypeNames::toStringArray()
                'instance' => static fn () => NodeTypeNames::fromStringArray(['Neos.Neos:Document', 'Neos.Neos:Content'])->toStringArray(),
                'invalid' => ['Neos.Neos:Document', ''],
            ],
            OriginDimensionSpacePointSet::class => [
                'instance' => static fn () => OriginDimensionSpacePointSet::fromArray([['language' => 'de'], ['language' => 'en']]),
                'invalid' => [['language' => 'de'], ['language' => '']],
            ],
            PropertyNames::class => [
                'instance' => static fn () => PropertyNames::fromArray(['title', 'text']),
                'invalid' => ['title', ''],
            ],
            SubtreeTags::class => [
                'instance' => static fn () => SubtreeTags::fromStrings('disabled', 'access-restricted'),
                'invalid' => ['disabled', '123'],
            ],
        ];
    }

    /**
     * @return array<class-string<\BackedEnum&ProvidesSchema>, true>
     */
    private static function enums(): array
    {
        return [
            NodeAggregateClassification::class => true,
            NodeVariantSelectionStrategy::class => true,
        ];
    }

    /**
     * @param class-string<ProvidesSchema> $class
     */
    #[DataProvider('schemaProviders')]
    public function testSchemaHasDescription(string $class): void
    {
        $schema = self::serializedSchema($class);
        // note: "anyOf" schemas can't be described themselves, so all of their alternatives have to be
        foreach ($schema['anyOf'] ?? [$schema] as $describedSchema) {
            $description = $describedSchema['description'] ?? null;
            self::assertIsString($description);
            self::assertNotSame('', $description);
        }
    }

    /**
     * @param class-string<ProvidesSchema> $class
     */
    #[DataProvider('schemaProviders')]
    public function testSchemaIsOnlyCreatedOnce(string $class): void
    {
        self::assertSame($class::schema(), $class::schema());
    }

    /**
     * @return iterable<string, array{class-string<ProvidesSchema>}>
     */
    public static function singleValueClasses(): iterable
    {
        foreach (array_keys(self::stringValueObjects()) as $class) {
            yield self::shortName($class) => [$class];
        }
        yield 'DimensionSpacePoint' => [DimensionSpacePoint::class];
        yield 'OriginDimensionSpacePoint' => [OriginDimensionSpacePoint::class];
    }

    /**
     * @param class-string<ProvidesSchema> $class
     */
    #[DataProvider('singleValueClasses')]
    public function testSchemaExamplesAreValidAndCanBeDeserialized(string $class): void
    {
        $examples = self::serializedSchema($class)['examples'] ?? [];
        self::assertNotEmpty($examples);
        foreach ($examples as $example) {
            self::assertTrue($class::schema()->validate($example)->valid, sprintf('Example %s is not valid', json_encode($example)));
            $instance = is_array($example) ? $class::fromArray($example) : $class::fromString($example);
            self::assertSame($example, $instance->jsonSerialize());
        }
    }

    /**
     * @return iterable<string, array{class-string<ProvidesSchema>, string}>
     */
    public static function validStringValues(): iterable
    {
        foreach (self::stringValueObjects() as $class => ['valid' => $values]) {
            foreach ($values as $value) {
                yield self::shortName($class) . ': "' . $value . '"' => [$class, $value];
            }
        }
    }

    /**
     * @param class-string<ProvidesSchema> $class
     */
    #[DataProvider('validStringValues')]
    public function testValidStringValuesAreAcceptedBySchemaAndConstructor(string $class, string $value): void
    {
        self::assertTrue($class::schema()->validate($value)->valid);
        self::assertSame($value, $class::fromString($value)->jsonSerialize());
    }

    /**
     * @return iterable<string, array{class-string<ProvidesSchema>, string}>
     */
    public static function invalidStringValues(): iterable
    {
        foreach (self::stringValueObjects() as $class => ['invalid' => $values]) {
            foreach ($values as $value) {
                yield self::shortName($class) . ': "' . $value . '"' => [$class, $value];
            }
        }
    }

    /**
     * @param class-string<ProvidesSchema> $class
     */
    #[DataProvider('invalidStringValues')]
    public function testInvalidStringValuesAreRejectedBySchemaAndConstructor(string $class, string $value): void
    {
        self::assertFalse($class::schema()->validate($value)->valid);
        $this->expectException(\InvalidArgumentException::class);
        $class::fromString($value);
    }

    /**
     * @return iterable<string, array{class-string<ProvidesSchema>}>
     */
    public static function collectionClasses(): iterable
    {
        foreach (array_keys(self::collections()) as $class) {
            yield self::shortName($class) => [$class];
        }
    }

    /**
     * @param class-string<ProvidesSchema> $class
     */
    #[DataProvider('collectionClasses')]
    public function testSerializedCollectionIsValid(string $class): void
    {
        $serialized = json_decode(json_encode(self::collections()[$class]['instance'](), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($class::schema()->validate($serialized)->valid);
        self::assertTrue($class::schema()->validate([])->valid);
    }

    /**
     * @param class-string<ProvidesSchema> $class
     */
    #[DataProvider('collectionClasses')]
    public function testCollectionWithInvalidItemIsRejected(string $class): void
    {
        self::assertFalse($class::schema()->validate(self::collections()[$class]['invalid'])->valid);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function serializedNodeAggregateIds(): iterable
    {
        yield 'list' => ['["abc","def"]'];
        yield 'object' => ['{"abc":"abc","def":"def"}'];
        yield 'object with numeric keys' => ['{"123":"123","abc":"abc"}'];
        yield 'object that PHP serializes as list' => ['{"0":"0","1":"1"}'];
    }

    #[DataProvider('serializedNodeAggregateIds')]
    public function testNodeAggregateIdsSchemaAcceptsAllSerializedForms(string $json): void
    {
        $nodeAggregateIds = NodeAggregateIds::fromJsonString($json);
        self::assertTrue(NodeAggregateIds::schema()->validate(json_decode($json, true, 512, JSON_THROW_ON_ERROR))->valid);
        self::assertTrue(NodeAggregateIds::schema()->validate(json_decode($nodeAggregateIds->toJson(), true, 512, JSON_THROW_ON_ERROR))->valid);
    }

    /**
     * @return iterable<string, array{class-string<\BackedEnum&ProvidesSchema>}>
     */
    public static function enumClasses(): iterable
    {
        foreach (array_keys(self::enums()) as $class) {
            yield self::shortName($class) => [$class];
        }
    }

    /**
     * @param class-string<\BackedEnum&ProvidesSchema> $class
     */
    #[DataProvider('enumClasses')]
    public function testEnumSchemaListsAllCases(string $class): void
    {
        self::assertSame(array_column($class::cases(), 'value'), self::serializedSchema($class)['enum']);
        self::assertFalse($class::schema()->validate('invalid')->valid);
    }

    /**
     * @return iterable<string, array{class-string<ProvidesSchema>, string, int}>
     */
    public static function exceptionCodes(): iterable
    {
        yield 'ContentRepositoryId' => [ContentRepositoryId::class, 'Invalid', 1791889842];
        yield 'ContentStreamId' => [ContentStreamId::class, 'Invalid', 1791889844];
        yield 'NodeAggregateId' => [NodeAggregateId::class, 'Invalid', 1791889843];
        yield 'NodeTypeName' => [NodeTypeName::class, '', 1505835958];
        // note: fully numeric values used to be rejected with a separate code
        yield 'SubtreeTag (numeric)' => [SubtreeTag::class, '123', 1695467813];
        yield 'SubtreeTag (pattern)' => [SubtreeTag::class, 'invalid:tag', 1695467813];
    }

    /**
     * @param class-string<ProvidesSchema> $class
     */
    #[DataProvider('exceptionCodes')]
    public function testInvalidValuesAreRejectedWithTheirExceptionCode(string $class, string $value, int $expectedCode): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedCode);
        $class::fromString($value);
    }

    public function testInvalidPointsAreRejectedWithTheirExceptionCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791889847);
        new DimensionSpacePointSet([OriginDimensionSpacePoint::fromArray(['language' => 'de'])]);
    }

    public function testInvalidOriginPointsAreRejectedWithTheirExceptionCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791889848);
        new OriginDimensionSpacePointSet([DimensionSpacePoint::fromArray(['language' => 'de'])]);
    }

    /**
     * @param class-string<ProvidesSchema> $class
     * @return array<string, mixed>
     */
    private static function serializedSchema(string $class): array
    {
        return json_decode(json_encode($class::schema(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private static function shortName(string $class): string
    {
        return substr($class, strrpos($class, '\\') + 1);
    }
}
