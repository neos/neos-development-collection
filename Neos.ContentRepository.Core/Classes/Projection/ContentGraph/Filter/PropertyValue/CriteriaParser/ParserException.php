<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\CriteriaParser;

/**
 * An exception that is thrown if a specified query could not be parsed by the @see PropertyValueCriteriaParser
 *
 * The message will contain a pointer to the parsing error:
 *
 *   Unable to parse character
 *   prop = invalüd character
 *   ------------^
 *
 * Example:
 *
 * try {
 *   PropertyValueCriteriaParser::parse($query);
 * } catch (ParserException $e) {
 *   echo substr($e->input, 0, $e->offset) . '<del>' . substr($e->input, $e->offset) . '</del>');
 * }
 *
 * // This will render "prop = inval<del>üd character</del>"
 *
 * *NOTE:* This is a simplified example, the user input and exception messages should never be rendered to the client without sanitization!
 *
 * @api This exception is not meant to be thrown by external code, but it can (and should) be handled
 */
final class ParserException extends \InvalidArgumentException
{
    public function __construct(
        public readonly string $customMessage,
        public readonly string $input,
        public readonly int $offset,
    ) {
        $message = $this->customMessage;
        if ($this->input !== '') {
            $message .= PHP_EOL . $this->input . PHP_EOL . str_repeat('-', $this->offset) . '^';
        }
        parent::__construct($message);
    }
}
