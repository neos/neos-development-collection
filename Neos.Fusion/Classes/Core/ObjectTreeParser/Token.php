<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

class Token
{
    public const EOF = 1;

    public const SLASH_COMMENT = 2;
    public const HASH_COMMENT = 3;
    public const MULTILINE_COMMENT = 4;

    public const SPACE = 5;
    public const NEWLINE = 6;

    public const INCLUDE = 7;
    public const NAMESPACE = 8;

    public const META_PATH_START = 9;
    public const OBJECT_PATH_PART = 10;
    public const PROTOTYPE_START = 11;

    public const ASSIGNMENT = 12;
    public const COPY = 13;
    public const UNSET = 14;

    public const FUSION_OBJECT_NAME = 15;

    public const TRUE_VALUE = 16;
    public const FALSE_VALUE = 17;
    public const NULL_VALUE = 18;

    public const INTEGER = 19;
    public const FLOAT = 20;

    public const STRING = 21;
    public const CHAR = 22;

    public const EEL_EXPRESSION = 23;
    public const DSL_EXPRESSION_START = 24;
    public const DSL_EXPRESSION_CONTENT = 25;

    public const FILE_PATTERN = 26;

    public const DOT = 27;
    public const COLON = 28;
    public const RPAREN = 29;
    public const LBRACE = 30;
    public const RBRACE = 31;

    public function __construct(
        protected int $type,
        protected string $value,
    ) {
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Returns the constant representation of a given type.
     *
     * @param int $type The type as an integer
     *
     * @return string The string representation
     * @throws \LogicException
     */
    public static function typeToString(int $type): string
    {
        $stringRepresentation = array_search($type, static::getConstants(), true);

        if ($stringRepresentation === false) {
            throw new \LogicException("Token of type '$type' does not exist", 1637307344);
        }
        return $stringRepresentation;
    }

    /**
     * @Flow\CompileStatic
     */
    protected static function getConstants()
    {
        $reflection = new \ReflectionClass(self::class);
        return $reflection->getConstants();
    }
}
