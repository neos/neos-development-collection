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

/**
 * Creates exception messages for the Fusion parser
 */
class MessageCreator
{
    protected const VALID_OBJECT_PATH = "A valid object path is by '.' delimited path segments: alphanumeric and [:_-], prototype(Foo:Bar), in quotes, or a meta path starting with '@'.";

    public static function forParseStatement(MessageLinePart $next, MessageLinePart $prev): string
    {
        switch ($next->char()) {
            case '/':
                if ($next->char(1) === '*') {
                    return "Unclosed comment.";
                }
                return "Unexpected single {$next->charPrint()}. You can start a comment with '//' or '/*' or '#'.";
            case '"':
            case '\'':
                return "Unclosed quoted path.";
            case '{':
                return "Unexpected block start out of context. Check your number of curly braces.";
            case '}':
                return "Unexpected block end out of context. Check your number of curly braces.";
        }
        return "Unexpected statement starting with: {$next->charPrint()}. " . self::VALID_OBJECT_PATH;
    }

    public static function forParseEndOfStatement(MessageLinePart $next, MessageLinePart $prev): string
    {
        switch ($next->char()) {
            case '/':
                if ($next->char(1) === '*') {
                    return "Unclosed comment.";
                }
                return "Unexpected single {$next->charPrint()}. You can start a comment with '//' or '/*' or '#'.";
        }
        return "Expected end of a statement but found {$next->linePrint()}.";
    }

    public static function forParsePathSegment(MessageLinePart $next, MessageLinePart $prev): string
    {
        switch ($next->char()) {
            case '"':
            case '\'':
                return "A quoted object path starting with {$next->charPrint()} was not closed.";
            case ' ':
                if ($prev->char(-1) === '.') {
                    return "No <space> is allowed after a separating '.' in an object path.";
                }
                return "Unexpected {$next->charPrint()} in object path.";
        }
        return "Unexpected object path starting with: {$next->charPrint()}. "  . self::VALID_OBJECT_PATH;
    }

    public static function forPathSegmentPrototypeName(MessageLinePart $next, MessageLinePart $prev): string
    {
        if (preg_match('/^[a-zA-Z0-9.]++(?!:)/', $next->line()) === 1) {
            return "Prototype name without namespace starting with {$next->charPrint()} - Default namespaces were removed. You might want to add 'Neos.Fusion:' in front.";
        }
        if (str_starts_with(trim($next->line()), ')')) {
            return "A prototype name must be set. Unexpected char {$next->charPrint()}.";
        }
        return "Unexpected prototype name starting with: {$next->linePrint()}.";
    }

    public static function forParsePathOrOperator(MessageLinePart $next, MessageLinePart $prev): string
    {
        if (preg_match('/.*namespace\s*:\s*$/', $prev->line()) === 1) {
            return "It looks like you want to declare a namespace alias. The feature to alias namespaces was removed.";
        }
        if (preg_match('/.*include\s*$/', $prev->line()) === 1) {
            return "Did you want to include a Fusion file? 'include: FileName.fusion'.";
        }
        if ($prev->char(-1) === ' ' && $next->char() === '.') {
            return "Nested paths, separated by {$next->charPrint()} cannot contain spaces.";
        }
        if ($prev->char(-1) === ' ') {
            // it might be an operator since there was space
            return "Unknown operator starting with {$next->charPrint()}. (Or you have unwanted spaces in your object path).";
        }
        if ($next->char() === '(') {
            return "An unquoted path segment cannot contain {$next->charPrint()}. Did you want to declare a prototype? 'prototype(Foo:Bar)'.";
        }
        if ($next->char() === '') {
            return "Object path without operator or block start. Found: {$next->charPrint()}.";
        }
        return "Unknown operator or path segment at {$next->charPrint()}. Unquoted paths can contain only alphanumerics and [:_-]. Otherwise, put them in quotes.";
    }

    public static function forParseDslExpression(MessageLinePart $next, MessageLinePart $prev): string
    {
        return "A dsl expression starting with {$next->linePrint(1)} was not closed.";
    }

    public static function forParsePathValue(MessageLinePart $next, MessageLinePart $prev): string
    {
        if (preg_match('/^[a-zA-Z0-9.]++(?!:)/', $next->line()) === 1) {
            return "Unexpected {$next->linePrint()} in value assignment - It looks like an object without namespace. Default namespaces were removed. You might want to add 'Neos.Fusion:' in front.";
        }
        switch ($next->char()) {
            case '':
                return "No value specified in assignment.";
            case '"':
                return "Unclosed quoted string.";
            case '\'':
                return "Unclosed char sequence.";
            case '`':
                return "Template literals without DSL identifier are not supported.";
            case '$':
                if ($next->char(1) === '{') {
                    return "Unclosed eel expression.";
                }
                return "Did you want to start an eel expression: '\${...}'?";
        }
        return "Unexpected character in assignment starting with {$next->charPrint()}.";
    }
}
