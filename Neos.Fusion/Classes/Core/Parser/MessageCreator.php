<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\Parser;

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
 * @internal
 */
class MessageCreator
{
    protected const VALID_OBJECT_PATH = "A valid object path is either alphanumeric and [:_-], prototype(Foo:Bar), in quotes, or a meta path starting with '@' delimited by a '.'.";

    public static function forParseStatement($nextPrint, $next, $nextPart): string
    {
        switch ($next) {
            case '/':
                if ($nextPart[1] ?? '' === '*') {
                    return "Unclosed comment.";
                }
                return "Unexpected single $nextPrint. You can start a comment with // or /* or #";
            case '"':
            case '\'':
                return "Unclosed quoted path.";
            case '{':
                return "Unexpected block start out of context. Check your number of curly braces.";
            case '}':
                return "Unexpected block end out of context. Check your number of curly braces.";
        }
        return "Unexpected statement starting with: $nextPrint. " . self::VALID_OBJECT_PATH;
    }

    public static function forParseEndOfStatement($nextPrint, $next, $nextPart): string
    {
        switch ($next) {
            case '/':
                if ($nextPart[1] ?? '' === '*') {
                    return "Unclosed comment.";
                }
                return "Unexpected single $nextPrint. You can start a comment with '//' or '/*' or '#'.";
        }
        $nextCharOrPart = mb_strlen($nextPart) > 1 ? "'$nextPart'" : $nextPrint;
        return "Expected end of a statement but found $nextCharOrPart.";
    }

    public static function forParsePathSegment($nextPrint, $next, $nextPart, $prev): string
    {
        switch ($next) {
            case '"':
            case '\'':
                return "A quoted object path starting with $nextPrint was not closed.";
            case ' ':
                if ($prev === '.') {
                    return "No <space> is allowed after a separating '.' in an object path.";
                }
                return "Unexpected $nextPrint in object path.";
        }
        return "Unexpected object path starting with: $nextPrint. "  . self::VALID_OBJECT_PATH;
    }

    public static function forParsePathOrOperator($nextPrint, $next, $nextPart, $prev, $prevPart): string
    {
        if (preg_match('/.*namespace\s*:\s*$/', $prevPart) === 1) {
            return "It looks like you want to declare a namespace alias. The feature to alias namespaces was removed.";
        }
        if (preg_match('/.*include\s*$/', $prevPart) === 1) {
            return "Did you want to include a Fusion file? 'include: FileName.fusion'";
        }
        if ($prev === ' ' && $next === '.') {
            return "Nested paths, separated by $nextPrint cannot contain spaces.";
        }
        if ($prev === ' ') {
            // it might be an operator since there was space
            return "Unknown operator starting with $nextPrint. (Or you have unwanted spaces in you object path)";
        }
        if ($next === '(') {
            return "An unquoted path segment cannot contain $nextPrint. Did you want to declare a prototype? 'prototype(Foo:Bar)'";
        }
        if ($next === '') {
            return "Object path without operator or block start. Found: $nextPrint";
        }
        return "Unknown operator or path segment at $nextPrint. Unquoted paths can contain only alphanumerics and [:_-]. Otherwise put them in quotes.";
    }

    public static function forParseDslExpression($nextPrint, $next, $nextPart): string
    {
        $dslCodeFistLine = substr($nextPart, 1);
        return "A dsl expression starting with '$dslCodeFistLine' was not closed.";
    }

    public static function forParsePathValue($nextPrint, $next, $nextPart): string
    {
        if (preg_match('/^[a-zA-Z0-9.]+/', $nextPart) === 1) {
            return "Unexpected '$nextPart' in value assignment - It looks like an object without namespace. But namespace alias were removed. You might want to add 'Neos.Fusion:' infront.";
        }
        switch ($next) {
            case '':
                return "No value specified in assignment.";
            case '"':
                return "Unclosed quoted string.";
            case '\'':
                return "Unclosed char sequence.";
            case '`':
                return "Template literals without DSL identifier are not supported.";
            case '$':
                if ($nextPart[1] ?? '' === '{') {
                    return "Unclosed eel expression.";
                }
                return "Did you want to start an eel expression: '\${...}'?";
        }
        return "Unexpected character in assignment starting with $nextPrint";
    }
}
