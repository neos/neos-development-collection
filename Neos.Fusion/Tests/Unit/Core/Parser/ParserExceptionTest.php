<?php
namespace Neos\Fusion\Tests\Unit\Core\Parser;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\ObjectTreeParser\PredictiveParser;
use Neos\Fusion\Core\ObjectTreeParser\Exception\ParserException;
use Neos\Flow\Tests\UnitTestCase;

class ParserExceptionTest extends UnitTestCase
{
    protected Parser $parser;

    public function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function fullParserExceptionMessage(): \Generator
    {
        yield 'no closing brace' => [
            <<<'FUSION'
            value {
                a = "fwef"
            FUSION,

            <<<'MESSAGE'
            <input>:1:7
              |
            1 | value {
              |       ^— column 7
            No closing brace "}" matched this starting block. Encountered <EOF>.
            MESSAGE
        ];

        yield 'unclosed eel' => [
            <<<'FUSION'

            a = ${
            FUSION,

            <<<'MESSAGE'
            <input>:2:5
              |
            2 | a = ${
              |     ^— column 5
            Unclosed eel expression.
            MESSAGE
        ];

        yield 'ugly underscore indentation, because the web removes space'  => [
            <<<'FUSION'

                    a      = Neos.Fusion:Value [
            FUSION,

            <<<'MESSAGE'
            <input>:2:36
              |
            2 |         a      = Neos.Fusion:Value [
              |                                    ^— column 36
            Expected end of a statement but found '['.
            MESSAGE

        ];

        yield 'uft 8 correct column cound'  => [
            <<<'FUSION'
            somepath.äöü = 123
            FUSION,

            <<<'MESSAGE'
            <input>:1:10
              |
            1 | somepath.äöü = 123
              |          ^— column 10
            Unexpected object path starting with: 'ä'. A valid object path is by '.' delimited path segments: alphanumeric and [:_-], prototype(Foo:Bar), in quotes, or a meta path starting with '@'.
            MESSAGE

        ];
    }

    public function generalInvalidFusion(): \Generator
    {
        yield 'space in object path' => [
            'path. hello = 123',
            "No <space> is allowed after a separating '.' in an object path."
        ];

        yield 'nested paths with space' => [
            'path.segment .nested = 0',
            "Nested paths, separated by '.' cannot contain spaces."
        ];

        yield 'reserved meta key' => [
            '  __meta = 1',
            'Reversed key \'__meta\' used.'
        ];

        yield 'prototype declaration without name' => [
            'prototype().moin = 123',
            "A prototype name must be set. Unexpected char ')'."
        ];

        yield 'prototype declaration without namespace' => [
            'prototype(Foo) < prototype(Neos.Fuison:Tag)',
            "Prototype name without namespace starting with 'F' - Default namespaces were removed. You might want to add 'Neos.Fusion:' in front."
        ];

        yield 'a path without operator or block' => [
            'path.something',
            "Object path without operator or block start. Found: <EOF>."
        ];

        yield 'no value' => [
            'a =',
            'No value specified in assignment.'
        ];

        yield 'invalid path segment (or operator)' => [
            'path&%324 = "value"',
            "Unknown operator or path segment at '&'. Unquoted paths can contain only alphanumerics and [:_-]. Otherwise, put them in quotes."
        ];

        yield 'invalid operator or (path segment)' => [
            'path := "value"',
            "Unknown operator starting with ':'. (Or you have unwanted spaces in your object path)."
        ];

        yield 'invalid char at start of path, when a path is expected' => [
            'path < äöü',
            "Unexpected object path starting with: 'ä'. A valid object path is by '.' delimited path segments: alphanumeric and [:_-], prototype(Foo:Bar), in quotes, or a meta path starting with '@'."
        ];

        yield 'invalid start of path in statement' => [
            'äöü = 0',
            "Unexpected statement starting with: 'ä'. A valid object path is by '.' delimited path segments: alphanumeric and [:_-], prototype(Foo:Bar), in quotes, or a meta path starting with '@'."
        ];
    }

    public function parsingWorksButOtherLogicThrows(): \Generator
    {
        yield 'invalid path to object inheritance' => [
            'prototype(a:b) < path.simple',
            'Cannot inherit, when one of the sides is no prototype definition of the form prototype(Foo). It is only allowed to build inheritance chains with prototype objects.'
        ];

        yield 'accidentally invalid nested object inheritance by missing end of block will complain about the }' => [
            'a {
            prototype(a:b) < prototype(b:b)',
            'No closing brace "}" matched this starting block. Encountered <EOF>.'
        ];

        yield 'invalid nested object inheritance' => [
            'nested.prototype(a:b) < prototype(b:b)',
            'Cannot inherit, when one of the sides is nested (e.g. foo.prototype(Bar)). Setting up prototype inheritance is only supported at the top level: prototype(Foo) < prototype(Bar)'
        ];
    }

    public function advancedGuessingWhatWentWrong(): \Generator
    {
        yield 'misspelled prototype declaration' => [
            'prooototype(a:b)',
            "An unquoted path segment cannot contain '('. Did you want to declare a prototype? 'prototype(Foo:Bar)'."
        ];

        yield 'include without colon' => [
            'include "pattern"',
            "Did you want to include a Fusion file? 'include: FileName.fusion'."
        ];
    }

    public function unclosedStatements(): \Generator
    {
        yield 'unclosed multiline comment' => [
            '/*',
            'Unclosed comment.'
        ];

        yield 'unclosed eel expression' => [
            'a = ${',
            'Unclosed eel expression.'
        ];

        yield 'unclosed string in value' => [
            'a = "hello',
            'Unclosed quoted string.'
        ];

        yield 'unclosed dsl expression' => [
            'a = afx`something',
            'A dsl expression starting with \'something\' was not closed.'
        ];

        yield 'unclosed block' => [
            'a {',
            'No closing brace "}" matched this starting block. Encountered <EOF>.'
        ];

        yield 'path with unclosed quoted path' => [
            'nested."path = 0',
            'A quoted object path starting with <double quote> was not closed.'
        ];

        yield 'unexpected block start' => [
            '{',
            "Unexpected block start out of context. Check your number of curly braces."
        ];

        yield 'unexpected block end' => [
            '}',
            "Unexpected block end out of context. Check your number of curly braces."
        ];
    }

    public function removedLanguageFeaturedAreExplained(): \Generator
    {
        yield 'unqualified object type' => [
            'a = Value',
            "Unexpected 'Value' in value assignment - It looks like an object without namespace. Default namespaces were removed. You might want to add 'Neos.Fusion:' in front."
        ];

        yield 'namespace alias declaration' => [
            'namespace: a=b',
            "It looks like you want to declare a namespace alias. The feature to alias namespaces was removed."
        ];
    }

    public function endOfLineExpected(): \Generator
    {
        yield 'multiple values' => [
            'a = 1 + 1',
            "Expected end of a statement but found '+ 1'."
        ];

        yield 'fusion object with space in value' => [
            'a = Vendor:Content. Component',
            "Expected end of a statement but found 'Component'."
        ];
    }

    /**
     * @test
     * @dataProvider fullParserExceptionMessage
     */
    public function itMatchesTheFullExceptionMessage($fusion, $expectedMessage): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage($expectedMessage);
        $this->parser->parse($fusion);
    }

    /**
     * @test
     * @dataProvider advancedGuessingWhatWentWrong
     * @dataProvider removedLanguageFeaturedAreExplained
     * @dataProvider generalInvalidFusion
     * @dataProvider parsingWorksButOtherLogicThrows
     * @dataProvider unclosedStatements
     * @dataProvider endOfLineExpected
     */
    public function itMatchesThePartialExceptionMessage($fusion, $expectedMessage): void
    {
        try {
            $this->parser->parse($fusion);
            self::fail('No exception was thrown. Expected message: ' . $expectedMessage);
        } catch (ParserException $e) {
            self::assertSame($expectedMessage, $e->getHelperMessagePart());
        }
    }
}
