<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Behat\Transliterator\Transliterator;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\JsonSchema\ProvidesSchema;
use Neos\JsonSchema\StringSchema;

/**
 * The Node name is the "path part" of the node; i.e. when accessing the node "/foo" via path {@see NodePath},
 * the node name is "foo".
 *
 * Semantically it describes the hierarchical relation of a node to its parent, e.g. "main" denotes the main child node.
 *
 * Multiple node names describe a node path {@see NodePath}
 *
 * To fetch the child node that is connected with the parent via the name "main" use the subgraph's: {@see ContentSubgraphInterface::findNodeByPath()}
 *
 * ```php
 * $subgraph->findNodeByPath(
 *     NodeName::fromString("main"),
 *     $parentNodeAggregateId
 * )
 * ```
 *
 * @api
 */
final readonly class NodeName implements ProvidesSchema, \JsonSerializable
{
    private const SCHEMA_PATTERN = '^[a-z0-9\-]+$';

    public const PATTERN = '/' . self::SCHEMA_PATTERN . '/';

    private function __construct(
        public string $value
    ) {
        $validationResult = self::schema()->validate($this->value);
        if (!$validationResult->valid) {
            throw new \InvalidArgumentException(sprintf('Invalid node name "%s": %s', $this->value, $validationResult->issues), 1364290748);
        }
    }

    public static function fromString(string $value): self
    {
        return new self(strtolower($value));
    }

    /**
     * Transforms a text into a valid name by removing invalid characters
     * and transliterating special characters if possible.
     *
     * @param string $name The possibly invalid name
     */
    public static function transliterateFromString(string $name): self
    {
        try {
            // Check if name already matches name pattern to prevent unnecessary transliteration
            return self::fromString($name);
        } catch (\InvalidArgumentException) {
            // okay, let's transliterate
        }

        $originalName = $name;

        // Transliterate (transform 北京 to 'Bei Jing')
        $name = Transliterator::transliterate($name);

        // Urlization (replace spaces with dash, special special characters)
        $name = Transliterator::urlize($name);

        // Ensure only allowed characters are left
        $name = preg_replace('/[^a-z0-9\-]/', '', $name);

        // Make sure we don't have an empty string left.
        if (empty($name)) {
            $name = 'node-' . strtolower(md5($originalName));
        }

        return new self($name);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function schema(): StringSchema
    {
        static $schema = null;
        return $schema ??= StringSchema::create(
            description: 'Name of a node, i.e. a segment of a node path',
            examples: ['main', 'my-document'],
            minLength: 1,
            pattern: self::SCHEMA_PATTERN,
        );
    }

    public function equals(NodeName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
