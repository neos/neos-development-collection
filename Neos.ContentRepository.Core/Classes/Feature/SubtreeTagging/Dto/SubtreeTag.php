<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto;

use Neos\JsonSchema\ProvidesSchema;
use Neos\JsonSchema\StringSchema;

/**
 * A tag that can be added to Node aggregates that is inherited by all its descendants
 *
 * @api
 */
final class SubtreeTag implements ProvidesSchema, \JsonSerializable
{
    /**
     * @var array<string,self>
     */
    private static array $instances = [];

    private static function instance(string $value): self
    {
        if (!array_key_exists($value, self::$instances)) {
            self::$instances[$value] = new self($value);
        }
        return self::$instances[$value];
    }

    private function __construct(public string $value)
    {
        $validationResult = self::schema()->validate($value);
        if (!$validationResult->valid) {
            throw new \InvalidArgumentException(sprintf('Invalid SubtreeTag value "%s": %s', $value, $validationResult->issues), 1695467813);
        }
    }

    public static function fromString(string $value): self
    {
        return self::instance($value);
    }

    public function equals(self $other): bool
    {
        return $this === $other;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function schema(): StringSchema
    {
        static $schema = null;
        // note: the negative lookahead rejects fully numeric values (as in {@see is_numeric()})
        return $schema ??= StringSchema::create(
            description: 'A tag that is added to a node aggregate and inherited by all its descendants',
            examples: ['disabled', 'access-restricted'],
            minLength: 1,
            maxLength: 36,
            pattern: '^(?!-?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:e-?[0-9]+)?$)[a-z0-9_.-]+$',
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /** -------------- deprecations: ------------------- */

    /**
     * Legacy, only for Neos.Neos context!, for standalone use please use {@see self::fromString()}
     *
     * Please use {@see \Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag::disabled()} instead.
     *
     * @deprecated with Neos 9 beta 19. To be removed with Neos 10.
     */
    public static function disabled(): self
    {
        return self::instance('disabled');
    }
}
