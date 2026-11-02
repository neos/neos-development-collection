<?php

declare(strict_types=1);

namespace Neos\Neos\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\JsonSchema\ProvidesSchema;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceTitle;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit test cases for the JSON schemas of the Neos domain model value objects that implement {@see ProvidesSchema}
 */
class ProvidesSchemaTest extends UnitTestCase
{
    /**
     * String value objects with values that are accepted and rejected by both, the schema and the `fromString()` constructor
     *
     * @return array<class-string<ProvidesSchema>, array{valid: list<string>, invalid: list<string>}>
     */
    private static function stringValueObjects(): array
    {
        return [
            SiteNodeName::class => [
                // note: the site node name is not validated
                'valid' => ['neosdemo', '', 'Not A Valid Node Name'],
                'invalid' => [],
            ],
            UserId::class => [
                'valid' => ['a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d', '1', str_repeat('a', 40)],
                'invalid' => ['', 'Admin', 'user_1', 'user 1', 'ä', str_repeat('a', 41)],
            ],
            WorkspaceDescription::class => [
                'valid' => ['', 'Workspace for the summer campaign.', 'Schöne Grüße, 日本!', 'a_b', str_repeat('ä', 500)],
                'invalid' => ['a+b', "a\tb", 'a😀', str_repeat('a', 501)],
            ],
            WorkspaceTitle::class => [
                'valid' => ['Live', 'John Doe\'s workspace', 'Schöne Grüße, 日本!', 'a_b', str_repeat('ä', 200)],
                'invalid' => ['', 'a+b', "a\tb", 'a😀', str_repeat('a', 201)],
            ],
        ];
    }

    /**
     * @return iterable<string, array{class-string<ProvidesSchema>}>
     */
    public static function schemaProviders(): iterable
    {
        foreach (array_keys(self::stringValueObjects()) as $class) {
            yield self::shortName($class) => [$class];
        }
    }

    /**
     * @param class-string<ProvidesSchema> $class
     */
    #[DataProvider('schemaProviders')]
    public function testSchemaHasDescription(string $class): void
    {
        $description = self::serializedSchema($class)['description'] ?? null;
        self::assertIsString($description);
        self::assertNotSame('', $description);
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
     * @param class-string<ProvidesSchema> $class
     */
    #[DataProvider('schemaProviders')]
    public function testSchemaExamplesAreValidAndCanBeDeserialized(string $class): void
    {
        $examples = self::serializedSchema($class)['examples'] ?? [];
        self::assertNotEmpty($examples);
        foreach ($examples as $example) {
            self::assertTrue($class::schema()->validate($example)->valid, sprintf('Example %s is not valid', json_encode($example)));
            self::assertSame($example, $class::fromString($example)->value);
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
        self::assertSame($value, $class::fromString($value)->value);
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
     * @return iterable<string, array{class-string<ProvidesSchema>, string, int}>
     */
    public static function exceptionCodes(): iterable
    {
        yield 'UserId' => [UserId::class, 'Admin', 1718293224];
        yield 'WorkspaceDescription' => [WorkspaceDescription::class, 'a+b', 1791889846];
        yield 'WorkspaceTitle' => [WorkspaceTitle::class, '', 1791889845];
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
