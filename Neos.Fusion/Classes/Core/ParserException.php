<?php
namespace Neos\Fusion\Core;
use Neos\Fusion;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An exception thrown by the Fusion parser. It will generate a message if the MESSAGE context flag is provided and show the current line with ASCII art.
 */
class ParserException extends Fusion\Exception
{
    public const MESSAGE_UNEXPECTED_CHAR = 1;
    public const MESSAGE_FROM_INPUT = 2;

    public const MESSAGE_PARSING_PATH_OR_OPERATOR = 4;
    public const MESSAGE_PARSING_PATH_SEGMENT = 8;
    public const MESSAGE_PARSING_VALUE_ASSIGNMENT = 16;
    public const MESSAGE_PARSING_DSL_EXPRESSION = 32;
    public const MESSAGE_PARSING_END_OF_STATEMENT = 64;
    public const MESSAGE_PARSING_STATEMENT = 128;
    public const HIDE_COLUMN = 256;

    private $fileName;
    private $lineNumber;
    private $columnNumber;
    private $currentLine;
    private $bitMaskOptions;
    private $isEof;
    private $optMessage;
    /**
     * @var string
     */
    private $firstPartOfLine;
    /**
     * @var string
     */
    private $lastPartOfLine;
    /**
     * @var string
     */
    private $nextChar;

    /**
     * @var string
     */
    private $nextCharPrint;

    /**
     * @var string
     */
    private $lastChar;

    /**
     * @param int $bitMaskOptions
     * @param array{?string, string, int} $currentParsingContext
     * @param int $messageCode
     * @param null $input
     * @param \Throwable|null $previous
     */
    public function __construct(int $bitMaskOptions, array $currentParsingContext, int $messageCode, $input = null, \Throwable $previous = null)
    {
        list($fileName, $code, $cursor) = $currentParsingContext;

        $this->fileName = $fileName ?? '<input>';

        $this->bitMaskOptions = $bitMaskOptions;
        $this->isEof = strlen($code) === $cursor;
        $this->optMessage = $input;

        $this->initializeCurrentCodePositionInformation($code, $cursor);

        $linePreview = $this->renderErrorLinePreview();
        $message = $this->getGeneratedMessage();

        $messageAndLinePreview = $linePreview . "\n" . $message;

        parent::__construct($messageAndLinePreview, $messageCode, $previous);
    }

    public function getGeneratedMessage(): string
    {
        return $this->getMessageByBitMaskOption();
    }

    protected function getMessageByBitMaskOption(): string
    {
        $m = [];
        if ($this->bitMaskOptions & self::MESSAGE_FROM_INPUT) {
            $m[] = $this->optMessage;
        }
        if ($this->bitMaskOptions & self::MESSAGE_UNEXPECTED_CHAR) {
            $m[] =  "Unexpected $this->nextCharPrint";
        }
        if ($this->bitMaskOptions & self::MESSAGE_PARSING_PATH_OR_OPERATOR) {
            $m[] =  $this->messageParsingPathOrOperator();
        }
        if ($this->bitMaskOptions & self::MESSAGE_PARSING_PATH_SEGMENT) {
            $m[] =  $this->messageParsingPathSegment();
        }
        if ($this->bitMaskOptions & self::MESSAGE_PARSING_VALUE_ASSIGNMENT) {
            $m[] =  $this->messageParsingValueAssignment();
        }
        if ($this->bitMaskOptions & self::MESSAGE_PARSING_DSL_EXPRESSION) {
            $m[] =  "A dsl expression starting with '$this->lastPartOfLine' was not closed.";
        }
        if ($this->bitMaskOptions & self::MESSAGE_PARSING_END_OF_STATEMENT) {
            $m[] =  $this->messageParsingEndOfStatement();
        }
        if ($this->bitMaskOptions & self::MESSAGE_PARSING_STATEMENT) {
            $m[] =  $this->messageParsingStatement();
        }
        return join(': ', $m);
    }

    protected function messageParsingEndOfStatement(): string
    {
        switch ($this->nextChar) {
            case '/':
                if ($this->lastPartOfLine[1] ?? '' === '*') {
                    return 'Unclosed comment.';
                }
                return 'Unexpected single /. You can start a comment with // or /* or #';
        }
        return "Expected the end of a statement but found '$this->lastPartOfLine'.";
    }

    protected function messageParsingPathOrOperator(): string
    {
        if (preg_match('/.*namespace\s*$/', $this->firstPartOfLine) === 1) {
            return 'Did you meant to add a namespace declaration? (namespace: Alias=Vendor)';
        }
        if (preg_match('/.*include\s*$/', $this->firstPartOfLine) === 1) {
            return 'Did you meant to include a Fusion file? (include: "./FileName.fusion")';
        }
        if ($this->lastChar === ' ' && $this->nextChar === '.') {
            return "Nested paths, seperated by '.' cannot contain spaces.";
        }
        if ($this->lastChar === ' ') {
            // it's an operator since there was space
            return "Unknown operator starting with $this->nextCharPrint. (Or you have unwanted spaces in you object path)";
        }
        if ($this->nextChar === '(') {
            return "A normal path segment cannot contain '('. Did you meant to declare a prototype: 'prototype()'?";
        }
        if ($this->nextChar === "\n" || $this->isEof) {
            return "Object path without operator or block - found: $this->nextCharPrint";
        }
        return "Unknown operator or path segment at $this->nextCharPrint. Paths can contain only alphanumeric and ':-' - otherwise quote them.";
    }

    protected function messageParsingPathSegment(): string
    {
        if ($this->nextChar === '"' || $this->nextChar === '\'') {
            return "A quoted object path starting with $this->nextChar was not closed";
        }
        return "Unexpected $this->nextCharPrint. Expected an object path like alphanumeric[:-], prototype(...), quoted paths, or meta path starting with @";
    }

    protected function messageParsingValueAssignment(): string
    {
        switch ($this->nextChar) {
            case '':
                return 'No value specified in assignment.';
            case '"':
                return 'Unclosed quoted string.';
            case '\'':
                return 'Unclosed char sequence.';
            case '`':
                return 'Template literals without DSL identifier are not supported.';
            case '$':
                if ($this->lastPartOfLine[1] ?? '' === '{') {
                    return 'Unclosed eel expression.';
                }
                return 'Did you meant to start an eel expression "${...}"?';
        }
        return "Unexpected character in assignment starting with $this->nextCharPrint";
    }

    protected function messageParsingStatement(): string
    {
        switch ($this->nextChar) {
            case '/':
                if ($this->lastPartOfLine[1] ?? '' === '*') {
                    return 'Unclosed comment.';
                }
                return 'Unexpected single /. You can start a comment with // or /* or #';
            case '"':
            case '\'':
                return 'Unclosed quoted path.';
            case '{':
                return 'Unexpected block start out of context. Check the number of your curly braces.';
            case '}':
                return 'Unexpected block end out of context. Check the number of your curly braces.';
        }
        return "Unexpected character in statement: $this->nextCharPrint. A valid object path is alphanumeric[:-], prototype(...), quoted, or a meta path starting with @";
    }

    protected function initializeCurrentCodePositionInformation(string $code, int $cursor): void
    {
        $codeLength = strlen($code);
        $newLinesFound = 0;
        $afterLastNewLineToCursor = '';
        $cursorToNextNewLine = '';

        // loop over the string char by char to determine the
        // current line of the $cursor and the part in the line in front
        // of the cursor and the remaining part of the line
        for ($i = 0; $i < $codeLength; ++$i) {
            $char = $code[$i];

            if ($i >= $cursor) {
                if ($char === "\n") {
                    break;
                }
                $cursorToNextNewLine .= $char;
                continue;
            }

            $afterLastNewLineToCursor .= $char;
            if ($char === "\n") {
                ++$newLinesFound;
                $afterLastNewLineToCursor = '';
            }
        }

        $this->lineNumber = $newLinesFound + 1;
        $this->firstPartOfLine = $afterLastNewLineToCursor;
        $this->lastPartOfLine = $cursorToNextNewLine;
        $this->currentLine = $afterLastNewLineToCursor . $cursorToNextNewLine;

        $this->columnNumber = mb_strlen($afterLastNewLineToCursor);

        $this->initNextAndLastChar($cursorToNextNewLine, $afterLastNewLineToCursor);
    }


    protected function initNextAndLastChar(string $cursorToNewLine, string $newLineToCursor): void
    {
        $this->nextChar = mb_substr($cursorToNewLine, 0, 1);

        $this->nextCharPrint = $this->isEof ? '<EOF>' : self::printable($this->nextChar);

        $this->lastChar = mb_substr($newLineToCursor, -1);
    }

    public function renderErrorLinePreview()
    {
        // maybe a little inspired by ^^ https://github.com/parsica-php/parsica/blob/main/src/Internal/Fail.php#L63

        $body = $this->currentLine;

        if ($this->isEof) {
            $body .= '<EOF>';
        }

        $lineNumber = $this->lineNumber;

        $spaceIndent = str_repeat('_', strlen((string)$lineNumber));

        // +1 to get the next char
        $columnNumber = $this->columnNumber + 1;

        $position = $this->fileName . ':' . $lineNumber;
        if (($this->bitMaskOptions & self::HIDE_COLUMN) === 0) {
            $position .= ':' . $columnNumber;
        }

        $spaceToArrow = str_repeat('_', $this->columnNumber);

        $body = preg_replace('/\s(?=\s)/', '_', $body);
        $bodyLine = strlen($body) > 80 ? (substr($body, 0, 77) . "...") : $body;

        $arrowColumn = '';
        if (($this->bitMaskOptions & self::HIDE_COLUMN) === 0) {
            $arrowColumn = "$spaceToArrow^— column $columnNumber";
        }

        return <<<MESSAGE
            $position
            {$spaceIndent} |
            {$lineNumber} | $bodyLine
            {$spaceIndent} | $arrowColumn
            MESSAGE;
    }

    public static function printable(string $char): string
    {
        if ($char === '') {
            return "<new line>";
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
