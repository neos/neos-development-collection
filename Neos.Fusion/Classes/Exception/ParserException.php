<?php
declare(strict_types=1);

namespace Neos\Fusion\Exception;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Exception;

/**
 * 'Fluent' exception for the Fusion Parser.
 */
class ParserException extends Exception
{
    protected const RESOURCE_PATH_TO_PACKAGE_AND_CLEANED_PATH_REGEXP = <<<'REGEX'
    `
      ^resource://                  # flow resource wrapper
      (?<packageKey>[^/]+)/         # Acme.Demo/
      (?:Private/Fusion/)?              # ignore default path Private/Fusion/
      (?:\.{1,2}/)*                       # ignore any kind of ./ or ../ greedy (also ./../../../)
      (?<fileLocationOutOfContext>.*)       # left over is a small clean file name with folders.
    `x
    REGEX;

    protected $fluentCode;
    protected $fluentFile;
    protected $fluentFusionCode;
    protected $fluentCursor;

    /**
     * @var ?callable
     */
    protected $fluentMessageCreator = null;
    protected $fluentPrevious = null;
    protected $fluentUnexpectedChar = false;
    protected $fluentShowColumn = true;

    protected $headingMessagePart;
    protected $asciiPreviewMessagePart;
    protected $helperMessagePart;

    public function __construct()
    {
    }

    /**
     * @api
     */
    public function getHeadingMessagePart(): string
    {
        return $this->headingMessagePart;
    }

    /**
     * @api
     */
    public function getAsciiPreviewMessagePart(): string
    {
        return $this->asciiPreviewMessagePart;
    }

    /**
     * @api
     */
    public function getHelperMessagePart(): string
    {
        return $this->helperMessagePart;
    }

    public function withCode(int $code): self
    {
        $this->fluentCode = $code;
        return $this;
    }

    public function withFile(?string $file): self
    {
        $this->fluentFile = $file;
        return $this;
    }

    public function withFusion(string $fusion): self
    {
        $this->fluentFusionCode = $fusion;
        return $this;
    }

    public function withCursor(int $cursor): self
    {
        $this->fluentCursor = $cursor;
        return $this;
    }

    public function withPrevious(?\Exception $previous): self
    {
        $this->fluentPrevious = $previous;
        return $this;
    }

    public function withoutColumnShown(): self
    {
        $this->fluentShowColumn = false;
        return $this;
    }

    /**
     * @param callable(string $nextCharPrint, string $nextChar, string $linePartAfterCursor, string $prevChar, string $linePartBeforeCursor):string $messageMaker
     */
    public function withMessageCreator(callable $messageCreator): self
    {
        $this->fluentMessageCreator = $messageCreator;
        return $this;
    }

    public function withMessage(string $message): self
    {
        return $this->withMessageCreator(static function() use ($message) {
            return $message;
        });
    }

    public function build(): self
    {
        $fullMessage = $this->renderAndInitializeFullMessage();
        parent::__construct($fullMessage, $this->fluentCode, $this->fluentPrevious);
        return $this;
    }

    protected function renderAndInitializeFullMessage(): string
    {
        if ($this->fluentMessageCreator === null || is_callable($this->fluentMessageCreator) === false) {
            throw new \LogicException('A callable message creator must be specified.', 1637307774);
        }

        if (isset($this->fluentFusionCode) === false) {
            throw new \LogicException('The fusion code must be specified.', 1637510580);
        }

        if (isset($this->fluentCursor) === false) {
            throw new \LogicException('The cursor position must be specified.', 1637510583);
        }

        list(
            $lineNumberCursor,
            $linePartAfterCursor,
            $linePartBeforeCursor
            ) = self::splitAtCursorGetLinePartsAndLineNumber($this->fluentFusionCode, $this->fluentCursor);

        $isEof = strlen($this->fluentFusionCode) === $this->fluentCursor;
        $columnNumber = mb_strlen($linePartBeforeCursor) + 1;

        $nextChar = mb_substr($linePartAfterCursor, 0, 1);

        $nextCharPrint = self::printable($nextChar, $isEof);

        $prevChar = mb_substr($linePartBeforeCursor, -1);

        $asciiPreviewMessagePart = self::renderErrorLinePreview(
            $this->fluentFile,
            $linePartBeforeCursor . $linePartAfterCursor,
            $lineNumberCursor,
            $columnNumber,
            $this->fluentShowColumn
        );

        $this->headingMessagePart = self::generateHeadingByFileName($this->fluentFile);
        $this->asciiPreviewMessagePart = $asciiPreviewMessagePart;
        $this->helperMessagePart = ($this->fluentMessageCreator)(
            $nextCharPrint,
            $nextChar,
            $linePartAfterCursor,
            $prevChar,
            $linePartBeforeCursor
        );

        if (FLOW_SAPITYPE === 'Web') {
            // if the exception is printed raw to the web, we need to put in there twice as many nonbreaking spaces as normal ones
            // to make the preview a bit okay looking.
            $asciiPreviewMessagePart = str_replace(' ', "\u{00A0}\u{00A0}", $asciiPreviewMessagePart);
        }

        $fullMessage = $this->headingMessagePart . PHP_EOL . $asciiPreviewMessagePart . PHP_EOL . $this->helperMessagePart;
        return $fullMessage;
    }

    protected static function renderErrorLinePreview(
        ?string $fileName,
        string $currentLine,
        int $lineNumber,
        int $columnNumber,
        bool $renderColumnDetails = true
    ): string {
        $body = $currentLine;
        $fileNameAndPosition = $fileName ?? '<input>' . ':' . $lineNumber;

        if ($renderColumnDetails) {
            $fileNameAndPosition .= ':' . $columnNumber;
        }

        $arrowColumn = '';
        if ($renderColumnDetails) {
            $spaceToArrow = str_repeat(' ', $columnNumber - 1);
            $arrowColumn = "$spaceToArrow^— column $columnNumber";
        }

        $indentLine = str_repeat(' ', strlen((string)$lineNumber));

        return <<<MESSAGE
            $fileNameAndPosition
            $indentLine |
            $lineNumber | $body
            $indentLine | $arrowColumn
            MESSAGE;
    }

    protected static function generateHeadingByFileName(?string $fileName): string
    {
        if ($fileName === null) {
            return 'Fusion parser exception while parsing. (No context path and filename set)';
        }
        if (preg_match(self::RESOURCE_PATH_TO_PACKAGE_AND_CLEANED_PATH_REGEXP, $fileName, $matches) === 1) {
            $packageKey = $matches['packageKey'];
            $fileLocationOutOfContext = $matches['fileLocationOutOfContext'];
            return "Fusion parser exception in '$fileLocationOutOfContext' of the package '$packageKey'.";
        }
        return "Fusion parser exception.";
    }

    /**
     * @return array{int, string, string}
     */
    protected static function splitAtCursorGetLinePartsAndLineNumber(string $string, int $cursor): array
    {
        $stringLength = \strlen($string);
        $lineNumberCursor = 1;
        $linePartBeforeCursor = '';
        $linePartAfterCursor = '';

        // loop over the string char by char to determine the
        // current line of the $cursor and the part in the line in front
        // of the cursor and the remaining part of the line
        for ($i = 0; $i < $stringLength; ++$i) {
            $char = $string[$i];

            if ($i >= $cursor) {
                if ($char === "\n") {
                    break;
                }
                $linePartAfterCursor .= $char;
                continue;
            }

            if ($char === "\n") {
                ++$lineNumberCursor;
                $linePartBeforeCursor = '';
                continue;
            }

            $linePartBeforeCursor .= $char;
        }

        return [$lineNumberCursor, $linePartAfterCursor, $linePartBeforeCursor];
    }

    protected static function printable(string $char, bool $isEof = false): string
    {
        if ($isEof) {
            return '<EOF>';
        }
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
