<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser\Exception;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Core\ObjectTreeParser\ExceptionMessage\MessageLinePart;
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

    protected int $fluentCode;
    protected \Closure $fluentMessageCreator;
    protected ?\Throwable $fluentPrevious = null;

    protected ?string $fluentFile;
    protected string $fluentFusion;
    protected int $fluentCursor;
    protected bool $fluentShowColumn = true;

    protected string $headingMessagePart;
    protected string $asciiPreviewMessagePart;
    protected string $helperMessagePart;

    public function __construct()
    {
    }

    public function getHeadingMessagePart(): string
    {
        return $this->headingMessagePart;
    }

    public function getAsciiPreviewMessagePart(): string
    {
        return $this->asciiPreviewMessagePart;
    }

    public function getHelperMessagePart(): string
    {
        return $this->helperMessagePart;
    }

    public function setCode(int $code): self
    {
        $this->fluentCode = $code;
        return $this;
    }

    public function setFile(?string $file): self
    {
        $this->fluentFile = $file;
        return $this;
    }

    public function setFusion(string $fusion): self
    {
        $this->fluentFusion = $fusion;
        return $this;
    }

    public function setCursor(int $cursor): self
    {
        $this->fluentCursor = $cursor;
        return $this;
    }

    public function setPrevious(?\Exception $previous): self
    {
        $this->fluentPrevious = $previous;
        return $this;
    }

    public function setHideColumnInformation(): self
    {
        $this->fluentShowColumn = false;
        return $this;
    }

    /**
     * @param callable(MessageLinePart $next, MessageLinePart $prev): string $messageCreator
     */
    public function setMessageCreator(callable $messageCreator): self
    {
        if ($messageCreator instanceof \Closure === false) {
            $messageCreator = \Closure::fromCallable($messageCreator);
        }
        $this->fluentMessageCreator = $messageCreator;
        return $this;
    }

    public function setMessage(string $message): self
    {
        return $this->setMessageCreator(static function () use ($message) {
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
        list(
            $lineNumberCursor,
            $linePartAfterCursor,
            $linePartBeforeCursor
        ) = self::splitAtCursorGetLinePartsAndLineNumber($this->fluentFusion, $this->fluentCursor);

        $isEof = strlen($this->fluentFusion) === $this->fluentCursor;
        $nextLine = new MessageLinePart($linePartAfterCursor, $isEof);
        $prevLine = new MessageLinePart($linePartBeforeCursor);

        $columnNumber = mb_strlen($linePartBeforeCursor) + 1;

        $this->headingMessagePart = self::generateHeadingByFileName($this->fluentFile);
        $this->asciiPreviewMessagePart = self::renderErrorLinePreview(
            $this->fluentFile,
            $linePartBeforeCursor . $linePartAfterCursor,
            $lineNumberCursor,
            $columnNumber,
            $this->fluentShowColumn
        );
        $this->helperMessagePart = ($this->fluentMessageCreator)(
            $nextLine,
            $prevLine,
        );

        $fullMessage = $this->headingMessagePart . PHP_EOL . $this->asciiPreviewMessagePart . PHP_EOL . $this->helperMessagePart;
        return $fullMessage;
    }

    protected static function renderErrorLinePreview(
        ?string $fileName,
        string $currentLine,
        int $lineNumber,
        int $columnNumber,
        bool $renderColumnDetails = true
    ): string {
        $fileNameAndPosition = ($fileName ?? '<input>') . ":$lineNumber" . ($renderColumnDetails ? ":$columnNumber" : '');

        $arrowColumn = '';
        if ($renderColumnDetails) {
            $spaceToArrow = str_repeat(' ', $columnNumber - 1);
            $arrowColumn = "$spaceToArrow^— column $columnNumber";
        }

        // convert the string length of a number to spaces, '72' becomes '  '.
        $indentLine = str_repeat(' ', strlen((string)$lineNumber));

        return <<<MESSAGE
            $fileNameAndPosition
            $indentLine |
            $lineNumber | $currentLine
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

            // keep looping we still need to reach the cursor position
            // but count the line breaks
            // and save the current portion after a line break
            if ($i < $cursor) {
                if ($char === "\n") {
                    ++$lineNumberCursor;
                    $linePartBeforeCursor = '';
                    continue;
                }

                $linePartBeforeCursor .= $char;
                continue;
            }

            // we arrived at chars in front of the cursor
            if ($char === "\n") {
                break;
            }
            // collect the last line part until there is a break
            $linePartAfterCursor .= $char;
        }

        return [$lineNumberCursor, $linePartAfterCursor, $linePartBeforeCursor];
    }
}
