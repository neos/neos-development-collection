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
    /**
     * @var string
     */
    protected $linePart;

    /**
     * @var bool
     */
    protected $isEof;

    public function __construct(string $linePart, bool $isEof = false)
    {
        $this->linePart = $linePart;
        $this->isEof = $isEof;
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
        switch (mb_ord($char)) {
            case   0:
                return "<null>";
            case   9:
                return "<horizontal tab>";
            case  10:
                return "<line feed>";
            case  11:
                return "<vertical tab>";
            case  13:
                return "<carriage return>";
            case  25:
                return "<end of medium>";
            case  27:
                return "<escape>";
            case  32:
                return "<space>";
            case  34:
                return "<double quote>";
            case  39:
                return "<single quote>";
            case  47:
                return "<slash>";
            case  92:
                return "<backslash>";
            case 130:
                return "<single low-9 quotation mark>";
            case 132:
                return "<double low-9 quotation mark>";
            case 145:
                return "<left single quotation mark>";
            case 146:
                return "<right single quotation mark>";
            case 147:
                return "<left double quotation mark>";
            case 148:
                return "<right double quotation mark>";
            case 160:
                return "<non-breaking space>";
            default:
                return "'$char'";
        }
    }
}
