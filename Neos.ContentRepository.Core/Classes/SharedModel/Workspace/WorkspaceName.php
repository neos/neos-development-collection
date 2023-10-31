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

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

use Behat\Transliterator\Transliterator;

/**
 * Name of a workspace.
 *
 * @api
 */
final class WorkspaceName implements \JsonSerializable
{
    public const WORKSPACE_NAME_LIVE = 'live';

    /**
     * @var array<string,self>
     */
    private static array $instances = [];

    private function __construct(
        public readonly string $value
    ) {
        if (preg_match('/^[\p{L}\p{P}\d \.]{1,200}$/u', $value) !== 1) {
            throw new \InvalidArgumentException('Invalid workspace name given.', 1505826610318);
        }
    }

    private static function instance(string $name): self
    {
        return self::$instances[$name] ??= new self($name);
    }

    public static function fromString(string $value): self
    {
        return self::instance($value);
    }

    public static function forLive(): self
    {
        return self::instance(self::WORKSPACE_NAME_LIVE);
    }

    /**
     * Transforms a text (for example a workspace title) into a valid workspace name by removing invalid characters
     * and transliterating special characters if possible.
     *
     * @param string $name The possibly invalid name
     */
    public static function transliterateFromString(string $name): self
    {
        try {
            // Check if name already match name pattern to prevent unnecessary transliteration
            return self::fromString($name);
        } catch (\InvalidArgumentException $e) {
            // Okay, let's transliterate
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
            $name = 'workspace-' . strtolower(md5($originalName));
        }

        return new self($name);
    }

    public function isLive(): bool
    {
        return $this->value === self::WORKSPACE_NAME_LIVE;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this === $other;
    }
}
