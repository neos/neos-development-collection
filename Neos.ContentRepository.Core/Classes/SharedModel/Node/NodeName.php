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
final readonly class NodeName implements \JsonSerializable
{
    public const PATTERN = '/^[a-z0-9\-]+$/';

    private function __construct(
        public string $value
    ) {
        if (preg_match(self::PATTERN, $this->value) !== 1) {
            throw new \InvalidArgumentException(
                'Invalid node name "' . $this->value
                    . '" (a node name must only contain lowercase characters, numbers and the "-" sign).',
                1364290748
            );
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

    public function equals(NodeName $other): bool
    {
        return $this->value === $other->value;
    }
}
