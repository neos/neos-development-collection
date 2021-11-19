<?php
declare(strict_types=1);

namespace Neos\Fusion\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion;

class Token
{
    // TODO rearrange
    public const EOF = -1;

    public const SLASH_COMMENT = 0;
    public const HASH_COMMENT = 1;
    public const MULTILINE_COMMENT = 2;

    public const NEWLINE = 3;
    public const SPACE = 4;

    public const META_PATH_START = 36;
    public const OBJECT_PATH_PART = 35;

    public const FUSION_OBJECT_NAME = 39;

    public const TRUE_VALUE = 5;
    public const FALSE_VALUE = 6;
    public const NULL_VALUE = 7;

    public const INTEGER = 41;
    public const FLOAT = 45;

    public const STRING = 31;
    public const CHAR = 32;

    public const EEL_EXPRESSION = 33;
    public const DSL_EXPRESSION_START = 43;
    public const DSL_EXPRESSION_CONTENT = 44;

    public const PROTOTYPE_START = 13;
    public const INCLUDE = 11;
    public const NAMESPACE = 12;

    public const ASSIGNMENT = 26;
    public const COPY = 27;
    public const UNSET = 28;

    public const DOT = 15;
    public const COLON = 16;
    public const RPAREN = 18;
    public const LBRACE = 19;
    public const RBRACE = 20;

    public const FILE_PATTERN = 40;

    /** @var string  */
    protected $value;

    /** @var int  */
    protected $type;

    /**
     * @param int $type The type of the token
     * @param string $value The token value
     */
    public function __construct(int $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
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
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        $stringRepresentation = array_search($type, $constants, true);

        if ($stringRepresentation === false) {
            throw new \LogicException("Token of type '$type' does not exist", 1637307344);
        }
        return $stringRepresentation;
    }
}
