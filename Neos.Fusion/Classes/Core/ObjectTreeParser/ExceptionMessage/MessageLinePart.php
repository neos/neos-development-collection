<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser\ExceptionMessage;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class MessageLinePart
{
    public function __construct(
        protected string $linePart,
        protected bool $isEof = false
    ) {
    }

    public function line(int $offset = 0): string
    {
        return mb_substr($this->linePart, $offset);
    }

    public function linePrint(int $offset = 0): string
    {
        $line = $this->line($offset);
        if (mb_strlen($line) > 1) {
            return "'$line'";
        }
        return $this->charPrint($offset);
    }

    public function char(int $index = 0): string
    {
        if ($index < 0) {
            return mb_substr($this->linePart, $index, 1);
        }
        return mb_substr($this->linePart, $index, $index + 1);
    }

    public function charPrint(int $index = 0): string
    {
        if ($this->isEof) {
            return '<EOF>';
        }
        return self::printable($this->char($index));
    }

    protected static function printable(string $char): string
    {
        if ($char === '') {
            return '<new line>';
        }

        // https://github.com/parsica-php/parsica/blob/main/src/Internal/Ascii.php
        return match (mb_ord($char)) {
            0 => "<null>",
            9 => "<horizontal tab>",
            10 => "<line feed>",
            11 => "<vertical tab>",
            13 => "<carriage return>",
            25 => "<end of medium>",
            27 => "<escape>",
            32 => "<space>",
            34 => "<double quote>",
            39 => "<single quote>",
            47 => "<slash>",
            92 => "<backslash>",
            130 => "<single low-9 quotation mark>",
            132 => "<double low-9 quotation mark>",
            145 => "<left single quotation mark>",
            146 => "<right single quotation mark>",
            147 => "<left double quotation mark>",
            148 => "<right double quotation mark>",
            160 => "<non-breaking space>",
            default => "'$char'",
        };
    }
}
