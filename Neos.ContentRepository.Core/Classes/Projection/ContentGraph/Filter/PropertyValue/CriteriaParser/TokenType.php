<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\CriteriaParser;

/**
 * Parser token types, @see PropertyValueCriteriaParser
 *
 * @internal
 */
enum TokenType
{
    case AND;
    case OR;
    case NOT;
    case TRUE;
    case FALSE;
    case INTEGER;
    case FLOAT;
    case PROPERTY_NAME;
    case STRING;
    case WHITESPACE;
    case STARTS_WITH;
    case NOT_EQUALS;
    case ENDS_WITH;
    case CONTAINS;
    case GREATER_THAN_OR_EQUAL;
    case GREATER_THAN;
    case LESS_THAN_OR_EQUAL;
    case LESS_THAN;
    case EQUALS;
    case PARENTHESIS_LEFT;
    case PARENTHESIS_RIGHT;
}
