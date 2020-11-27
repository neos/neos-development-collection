<?php
declare(strict_types=1);

namespace Neos\Fusion\Afx\Parser;

/*
 * This file is part of the Neos.Fusion.Afx package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A primitive lexer that recognizes Afx-specific characters while iterating
 * through a string
 * @package Neos\Fusion\Afx\Parser
 */
class Lexer
{
    /**
     * The string to be iterated through
     *
     * @var string
     */
    protected $string;

    /**
     * The currently focused character
     *
     * @var string
     */
    protected $currentCharacter;

    /**
     * The current character position
     *
     * @var integer
     */
    protected $characterPosition;

    /**
     * Constructor
     *
     * @param string $string
     */
    public function __construct($string)
    {
        $this->string = $string;
        $this->currentCharacter = ($string !== '') ? $string[0] : null;
        $this->characterPosition = 0;
    }

    /**
     * Checks if the current character is whitespace
     *
     * @return boolean
     */
    public function isWhiteSpace(): bool
    {
        return ctype_space($this->currentCharacter);
    }

    /**
     * Checks if the current character is a letter
     *
     * @return boolean
     */
    public function isAlpha(): bool
    {
        return ctype_alpha($this->currentCharacter);
    }

    /**
     * Checks if the current character is alpha-numeric
     *
     * @return boolean
     */
    public function isAlphaNumeric(): bool
    {
        return ctype_alnum($this->currentCharacter);
    }

    /**
     * Checks if the current character is a colon
     *
     * @return boolean
     */
    public function isColon(): bool
    {
        return $this->currentCharacter === ':';
    }

    /**
     * Checks if the current character is a dot
     *
     * @return boolean
     */
    public function isDot(): bool
    {
        return $this->currentCharacter === '.';
    }

    /**
     * Checks if the current character is a @
     *
     * @return boolean
     */
    public function isAt(): bool
    {
        return $this->currentCharacter === '@';
    }

    /**
     * Checks if the current character is a minus
     *
     * @return boolean
     */
    public function isMinus(): bool
    {
        return $this->currentCharacter === '-';
    }

    /**
     * Checks if the current character is an underscore
     *
     * @return boolean
     */
    public function isUnderscore(): bool
    {
        return $this->currentCharacter === '_';
    }

    /**
     * Checks if the current character is an equal sign
     *
     * @return boolean
     */
    public function isEqualSign(): bool
    {
        return $this->currentCharacter === '=';
    }

    /**
     * Checks if the current character is an opening bracket
     *
     * @return boolean
     */
    public function isOpeningBracket(): bool
    {
        return $this->currentCharacter === '<';
    }

    /**
     * Checks if the current character is a closing bracket
     *
     * @return boolean
     */
    public function isClosingBracket(): bool
    {
        return $this->currentCharacter === '>';
    }

    /**
     * Checks if the current character is an opening curly brace
     *
     * @return boolean
     */
    public function isOpeningBrace(): bool
    {
        return $this->currentCharacter === '{';
    }

    /**
     * Checks if the current character is a closing curly brace
     *
     * @return boolean
     */
    public function isClosingBrace(): bool
    {
        return $this->currentCharacter === '}';
    }

    /**
     * Checks if the current character is a forward slash
     *
     * @return boolean
     */
    public function isForwardSlash(): bool
    {
        return $this->currentCharacter === '/';
    }

    /**
     * Checks if the current character is a back slash
     *
     * @return boolean
     */
    public function isBackSlash(): bool
    {
        return $this->currentCharacter === '\\';
    }

    /**
     * Checks if the current character is a single quote
     *
     * @return boolean
     */
    public function isSingleQuote(): bool
    {
        return $this->currentCharacter === '\'';
    }

    /**
     * Checks if the current character is a double quote
     *
     * @return boolean
     */
    public function isDoubleQuote(): bool
    {
        return $this->currentCharacter === '"';
    }

    /**
     * Checks if the current character is an exclamation mark
     *
     * @return boolean
     */
    public function isExclamationMark(): bool
    {
        return $this->currentCharacter === '!';
    }

    /**
     * Checks if the iteration has ended
     *
     * @return boolean
     */
    public function isEnd(): bool
    {
        return $this->currentCharacter === null;
    }

    /**
     * Rewinds the iteration by one step
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->currentCharacter = $this->string[--$this->characterPosition];
    }

    /**
     * Peek several characters in advance and return the next n characters
     *
     * @param int $characterNumber
     * @return string|null
     */
    public function peek($characterNumber = 1): ?string
    {
        if ($this->characterPosition < strlen($this->string) - 1) {
            return substr($this->string, $this->characterPosition, $characterNumber);
        } else {
            return null;
        }
    }

    /**
     * Returns the current character and moves one step forward
     *
     * @return string|null
     */
    public function consume(): ?string
    {
        $c = $this->currentCharacter;
        if ($this->characterPosition < strlen($this->string) - 1) {
            $this->currentCharacter = $this->string[++$this->characterPosition];
        } else {
            $this->currentCharacter = null;
        }

        return $c;
    }
}
