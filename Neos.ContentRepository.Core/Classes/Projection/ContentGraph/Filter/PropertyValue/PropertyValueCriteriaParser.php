<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\AndCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\NegateCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\OrCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueContains;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEndsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueStartsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\CriteriaParser\ParserException;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\CriteriaParser\Token;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\CriteriaParser\TokenType;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * Parser to turn a string based property value filter into an AST
 *
 * The following comparisons are supported:
 *
 * property value equals the specified value (string, integer, float or boolean):
 *
 *  "prop = 'some string'"
 *  "prop = 123"
 *  "prop = 123.45"
 *  "prop = true"
 *
 * property value is NOT equal to the specified value (string, integer, float or boolean):
 *
 *  "prop != 'some string'"
 *  "prop != 123"
 *  "prop != 123.45"
 *  "prop != true"
 *
 * property value ends with the specified substring
 *
 *  "prop $= 'some string'"
 *
 * property value starts with the specified substring
 *
 *  "prop ^= 'some string'"
 *
 * property value contains the specified substring
 *
 *  "prop *= 'some string'"
 *
 * property value is greater than the specified value (string, integer or float):
 *
 *  "prop > 'some string'"
 *  "prop > 123"
 *  "prop > 123.45"
 *
 * property value is greater than or equal to the specified value (string, integer or float):
 *
 *  "prop >= 'some string'"
 *  "prop >= 123"
 *  "prop >= 123.45"
 *
 * property value is less than the specified value (string, integer or float):
 *
 *  "prop < 'some string'"
 *  "prop < 123"
 *  "prop < 123.45"
 *
 * property value is less than or equal to the specified value (string, integer or float):
 *
 *  "prop <= 'some string'"
 *  "prop <= 123"
 *  "prop <= 123.45"
 *
 * criteria can be combined using "AND" and "OR":
 *
 *  "prop1 ^= 'foo' AND (prop2 = 'bar' OR prop3 = 'baz')"
 *
 * furthermore "NOT" can be used to negate a whole sub query
 *
 *  "prop1 ^= 'foo' AND NOT (prop2 = 'bar' OR prop3 = 'baz')"
 *
 * Example:
 *
 * $propertyValueCriteria = PropertyValueCriteriaParser::parse(prop1 ^= "foo" AND prop $= "bar");
 * $contentSubgraph->findChildNodes($parentNodeId, FindChildNodesFilter::create()->with(propertyValue: $propertyValueCriteria));
 *
 * @see ContentSubgraphInterface
 * @api
 */
final class PropertyValueCriteriaParser
{
    private static int $index = 0;

    /**
     * @var Token[]
     */
    private static array $tokens = [];

    private static string $query = '';

    private const TOKEN_PATTERNS = [
        '/^(AND)/' => TokenType::AND,
        '/^(OR)/' => TokenType::OR,
        '/^(NOT)/' => TokenType::NOT,
        '/^(true|TRUE)/' => TokenType::TRUE,
        '/^(false|FALSE)/' => TokenType::FALSE,
        '/^(\d+\.\d+)/' => TokenType::FLOAT,
        '/^(\d+)/' => TokenType::INTEGER,
        '/^(\w{1,100})/' => TokenType::PROPERTY_NAME,
        '/^(["\'])(?<match>(\\\\{2})*|(.*?[^\\\\](\\\\{2})*))\1/' => TokenType::STRING,
        '/^(\s+)/' => TokenType::WHITESPACE,
        '/^(\^=)/' => TokenType::STARTS_WITH,
        '/^(\!=)/' => TokenType::NOT_EQUALS,
        '/^(\$=)/' => TokenType::ENDS_WITH,
        '/^(\*=)/' => TokenType::CONTAINS,
        '/^(\>=)/' => TokenType::GREATER_THAN_OR_EQUAL,
        '/^(\>)/' => TokenType::GREATER_THAN,
        '/^(\<=)/' => TokenType::LESS_THAN_OR_EQUAL,
        '/^(\<)/' => TokenType::LESS_THAN,
        '/^(=)/' => TokenType::EQUALS,
        '/^(\()/' => TokenType::PARENTHESIS_LEFT,
        '/^(\))/' => TokenType::PARENTHESIS_RIGHT,
    ];

    /**
     * @throws ParserException if the query could not be parsed
     */
    public static function parse(string $query): PropertyValueCriteriaInterface
    {
        self::$index = 0;
        self::$query = trim($query);
        self::$tokens = [];
        if (self::$query === '') {
            self::throwParserException('Query must not be empty', 0);
        }
        self::tokenize();
        return self::parseExpression();
    }

    private static function tokenize(): void
    {
        self::$tokens = [];
        $offset = 0;
        while ($offset < strlen(self::$query)) {
            $token = self::matchToken($offset);
            $offset += $token->offsetEnd - $token->offsetStart;
            self::$tokens[] = $token;
        }
    }

    private static function matchToken(int $offset): Token
    {
        $substring = substr(self::$query, $offset);
        foreach (self::TOKEN_PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $substring, $matches)) {
                return new Token(
                    $type,
                    $matches['match'] ?? $matches[1],
                    $offset,
                    $offset + strlen($matches[0]),
                );
            }
        }
        self::throwParserException('Unable to parse character', count(self::$tokens) - 1);
    }

    private static function parseExpression(): PropertyValueCriteriaInterface
    {
        $left = self::parseSimpleExpression();

        while (self::match(TokenType::AND, TokenType::OR)) {
            $operator = self::previous();
            $right = self::parseSimpleExpression();
            $left = match ($operator->type) {
                TokenType::AND => AndCriteria::create($left, $right),
                TokenType::OR => OrCriteria::create($left, $right),
                default => throw new \RuntimeException(sprintf('Unexpected expression token type "%s"', $operator->type->name), 1679589218),
            };
        }

        return $left;
    }

    private static function parseSimpleExpression(): PropertyValueCriteriaInterface
    {
        if (self::match(TokenType::NOT)) {
            $expr = self::parseExpression();
            return NegateCriteria::create($expr);
        }
        if (self::match(TokenType::PARENTHESIS_LEFT)) {
            $expr = self::parseExpression();
            self::consume(TokenType::PARENTHESIS_RIGHT, 'Expecting a closing parenthesis');
            return $expr;
        }
        return self::parseComparison();
    }

    private static function parseComparison(): PropertyValueCriteriaInterface
    {
        $left = self::consume(TokenType::PROPERTY_NAME, 'Expecting a property name.');

        $comparison_operators = [
            TokenType::STARTS_WITH,
            TokenType::NOT_EQUALS,
            TokenType::ENDS_WITH,
            TokenType::CONTAINS,
            TokenType::GREATER_THAN_OR_EQUAL,
            TokenType::GREATER_THAN,
            TokenType::LESS_THAN_OR_EQUAL,
            TokenType::LESS_THAN,
            TokenType::EQUALS
        ];
        $operator = self::consumeOneOf($comparison_operators, 'Expecting a comparison operator.');

        $right = self::consumeOneOf([TokenType::STRING, TokenType::TRUE, TokenType::FALSE, TokenType::INTEGER, TokenType::FLOAT], 'Expecting a value.');
        $value = match ($right->type) {
            TokenType::INTEGER => (int)$right->value,
            TokenType::FLOAT => (float)$right->value,
            TokenType::TRUE => true,
            TokenType::FALSE => false,
            default => $right->value,
        };

        $propertyName = PropertyName::fromString($left->value);
        try {
            return match ($operator->type) {
                TokenType::STARTS_WITH => PropertyValueStartsWith::create($propertyName, $value), // @phpstan-ignore-line
                TokenType::NOT_EQUALS => NegateCriteria::create(PropertyValueEquals::create($propertyName, $value)),
                TokenType::ENDS_WITH => PropertyValueEndsWith::create($propertyName, $value), // @phpstan-ignore-line
                TokenType::CONTAINS => PropertyValueContains::create($propertyName, $value), // @phpstan-ignore-line
                TokenType::GREATER_THAN_OR_EQUAL => PropertyValueGreaterThanOrEqual::create($propertyName, $value), // @phpstan-ignore-line
                TokenType::GREATER_THAN => PropertyValueGreaterThan::create($propertyName, $value), // @phpstan-ignore-line
                TokenType::LESS_THAN_OR_EQUAL => PropertyValueLessThanOrEqual::create($propertyName, $value), // @phpstan-ignore-line
                TokenType::LESS_THAN => PropertyValueLessThan::create($propertyName, $value), // @phpstan-ignore-line
                TokenType::EQUALS => PropertyValueEquals::create($propertyName, $value),
                default => self::throwParserException(sprintf('Invalid comparison token type "%s"', $operator->type->name), self::$index),
            };
        } catch (\TypeError $_) {
            self::throwParserException(sprintf('The %s operator does not support values of type %s', $operator->type->name, get_debug_type($value)), self::$index - 2);
        }
    }

    private static function match(TokenType ...$tokenTypes): bool
    {
        foreach ($tokenTypes as $type) {
            if (self::check($type)) {
                self::$index++;
                return true;
            }
        }
        return false;
    }

    private static function check(TokenType $tokenType): bool
    {
        while (!self::isAtEnd() && self::$tokens[self::$index]->type === TokenType::WHITESPACE) {
            self::$index++;
        }
        if (self::isAtEnd()) {
            return false;
        }
        return self::$tokens[self::$index]->type === $tokenType;
    }

    private static function previous(): Token
    {
        return self::$tokens[max(self::$index - 1, 0)];
    }

    private static function consume(TokenType $tokenType, string $errorMessage): Token
    {
        if (self::check($tokenType)) {
            return self::$tokens[self::$index++];
        }
        self::throwParserException($errorMessage, self::$index - 1);
    }

    /**
     * @param TokenType[] $tokenTypes
     */
    private static function consumeOneOf(array $tokenTypes, string $errorMessage): Token
    {
        foreach ($tokenTypes as $type) {
            if (self::check($type)) {
                return self::$tokens[self::$index++];
            }
        }
        self::throwParserException($errorMessage, self::$index - 1);
    }

    private static function isAtEnd(): bool
    {
        return self::$index >= count(self::$tokens);
    }

    /**
     * @phpstan-return never
     */
    private static function throwParserException(string $message, int $tokenIndex): void
    {
        $token = self::$tokens[$tokenIndex] ?? null;
        throw new ParserException($message, self::$query, $token?->offsetEnd ?? 0);
    }
}
