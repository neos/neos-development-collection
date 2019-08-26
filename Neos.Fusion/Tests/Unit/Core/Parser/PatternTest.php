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
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Parser;

/**
 * Testcase for the Fusion Parser - tests the regex patterns
 *
 */
class PatternTest extends UnitTestCase
{
    /**
     * Checks the regular expression SCAN_PATTERN_COMMENT
     *
     * @test
     */
    public function testSCAN_PATTERN_COMMENT()
    {
        $pattern = Parser::SCAN_PATTERN_COMMENT;
        self::assertEquals(preg_match($pattern, '/* This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a block comment start.');
        self::assertEquals(preg_match($pattern, '# This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a hash comment start.');
        self::assertEquals(preg_match($pattern, '// This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a double slash comment start.');
        self::assertEquals(preg_match($pattern, ' # This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a hash comment start with preceeding whitespace.');
        self::assertEquals(preg_match($pattern, '/ This is not a comment start ...'), 0, 'The SCAN_PATTERN_COMMENT pattern matched a single slash.');
        self::assertEquals(preg_match($pattern, '*/ This is not a comment start ...'), 0, 'The SCAN_PATTERN_COMMENT pattern matched a comment block ending.');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_OPENINGCONFINEMENT()
    {
        $pattern = Parser::SCAN_PATTERN_OPENINGCONFINEMENT;
        self::assertEquals(preg_match($pattern, 'foo.bar.baz {'), 1, 'a confinement was not matched');
        self::assertEquals(preg_match($pattern, 'fo-o.bar-la.baz {'), 1, 'a confinement with dashes was not matched');
        self::assertEquals(preg_match($pattern, 'fo:o.bar:la.baz {'), 1, 'a confinement with colons was not matched');
        self::assertEquals(preg_match($pattern, 'f21oo.b12ar.baz {'), 1, 'a confinement with a number was not matched');
        self::assertEquals(preg_match($pattern, '		foo.bar.baz	    {	'), 1, 'a path which contained numerous whitespace was not matched');
        self::assertEquals(preg_match($pattern, 'f21oo.b12ar.baz { foo'), 0, 'a confinement with parts after the opening confinement matched');
        self::assertEquals(preg_match($pattern, '1foo.bar.baz {'), 1, 'a path which contained a number was matched (1)');
        self::assertEquals(preg_match($pattern, 'foo.1bar.baz {'), 1, 'a path which contained a number was matched (2)');
        self::assertEquals(preg_match($pattern, 'foo.1bar.\'b@r\' {'), 1, 'a path which contained a single quoted key was matched (1)');
        self::assertEquals(preg_match($pattern, 'foo.\'1b@r\'.\'b@r\' {'), 1, 'a path which contained a single quoted key was matched (2)');
        self::assertEquals(preg_match($pattern, 'foo.1bar."b@r" {'), 1, 'a path which contained a double quoted key was matched (1)');
        self::assertEquals(preg_match($pattern, 'foo."1b@r"."b@r" {'), 1, 'a path which contained a double quoted key was matched (2)');
        self::assertEquals(preg_match($pattern, 'foo."1b@r".\'b@r\' {'), 1, 'a path which contained a single & double quoted keys was matched');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_CLOSINGCONFINEMENT()
    {
        $pattern = Parser::SCAN_PATTERN_CLOSINGCONFINEMENT;
        self::assertEquals(preg_match($pattern, '}'), 1, 'a closing confinement was not matched');
        self::assertEquals(preg_match($pattern, '		  }'), 1, 'a closing confinement with leading whitespace was not matched');
        self::assertEquals(preg_match($pattern, '		  }     '), 1, 'a closing confinement with leading and following whitespace was not matched');
        self::assertEquals(preg_match($pattern, '		  }    assas '), 0, 'a closing confinement with following text was matched, although it should not.');
    }

    /**
     * Checks the regular expression SCAN_PATTERN_DECLARATION
     *
     * @test
     */
    public function testSCAN_PATTERN_DECLARATION()
    {
        $pattern = Parser::SCAN_PATTERN_DECLARATION;
        self::assertEquals(preg_match($pattern, 'include : source = "resource"'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an include declaration.');
        self::assertEquals(preg_match($pattern, 'include:source = "resource"'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an include declaration without whitespaces.');
        self::assertEquals(preg_match($pattern, 'namespace: cms = Test'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an namespace declaration.');
        self::assertEquals(preg_match($pattern, '  namespace: cms = Test'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an namespace declaration whith leading whitespace.');
        self::assertEquals(preg_match($pattern, 'ASDF  namespace: cms = Test'), 0, 'The SCAN_PATTERN_DECLARATION pattern did match an namespace declaration whith leading text.');
        self::assertEquals(preg_match($pattern, 'ASDF  namespace: Neos.Neos = Foo'), 0, 'The SCAN_PATTERN_DECLARATION pattern did match an namespace declaration whith leading text.');
        self::assertEquals(preg_match($pattern, '// This is a comment ...'), 0, 'The SCAN_PATTERN_DECLARATION pattern matched a comment.');
    }

    /**
     * Checks the regular expression SCAN_PATTERN_OBJECTDEFINITION
     *
     * @test
     */
    public function testSCAN_PATTERN_OBJECTDEFINITION()
    {
        $pattern = Parser::SCAN_PATTERN_OBJECTDEFINITION;
        self::assertEquals(preg_match($pattern, 'myObject = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment.');
        self::assertEquals(preg_match($pattern, '  myObject = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment with leading whitespace.');
        self::assertEquals(preg_match($pattern, 'myObject.content = "stuff"'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match a literal assignment of a property.');
        self::assertEquals(preg_match($pattern, 'my-object.con-tent = "stuff"'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match a dasherized path.');
        self::assertEquals(preg_match($pattern, 'my:object.con:tent = "stuff"'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match a colonrized path.');
        self::assertEquals(preg_match($pattern, 'myObject.10 = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment of a content array item.');
        self::assertEquals(preg_match($pattern, 'myObject.\'b@r\' = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment of a single quoted key.');
        self::assertEquals(preg_match($pattern, 'myObject."b@r" = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment of a double quoted key.');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_OBJECTPATH()
    {
        $pattern = Parser::SCAN_PATTERN_OBJECTPATH;
        self::assertEquals(preg_match($pattern, 'foo.bar'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match a simple object path (1)');
        self::assertEquals(preg_match($pattern, 'foo.\'b@r\''), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match a object path with a single quoted key');
        self::assertEquals(preg_match($pattern, 'foo."b@r"'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match a object path with a double quoted key');
        self::assertEquals(preg_match($pattern, 'foo.prototype(Neos.Foo).bar'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match an object path with a prototype definition inside (2)');
        self::assertEquals(preg_match($pattern, 'prototype(Neos.Foo)'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match an object path which consists only of a prototype definition (3)');
        self::assertEquals(preg_match($pattern, 'foo.bar.10.baz'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match a simple object path (4)');
        self::assertEquals(preg_match($pattern, 'foo.bar.as10.baz'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match a simple object path (5)');
        self::assertEquals(preg_match($pattern, '12foo.bar.as.baz'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did match a simple object path (6)');
        self::assertEquals(preg_match($pattern, '12f-o-o.ba-r.as.ba-z'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did match a simple object path with dashes (7)');
        self::assertEquals(preg_match($pattern, '12f:o:o.ba:r.as.ba:z'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did match a simple object path with colons (7)');
    }

    /**
     * @test
     */
    public function testSPLIT_PATTERN_OBJECTPATH()
    {
        $pattern = Parser::SPLIT_PATTERN_OBJECTPATH;

        $expected = [
            0 => 'foo',
            1 => 'bar'
        ];
        self::assertSame($expected, preg_split($pattern, 'foo.bar'));

        $expected = [
            0 => 'prototype(Neos.Foo)',
            1 => 'bar'
        ];
        self::assertSame($expected, preg_split($pattern, 'prototype(Neos.Foo).bar'));

        $expected = [
            0 => 'asdf',
            1 => 'prototype(Neos.Foo)',
            2 => 'bar'
        ];
        self::assertSame($expected, preg_split($pattern, 'asdf.prototype(Neos.Foo).bar'));

        $expected = [
            0 =>  'blah',
            1 => 'asdf',
            2 => 'prototype(Neos.Foo)',
            3 => 'bar'
        ];
        self::assertSame($expected, preg_split($pattern, 'blah.asdf.prototype(Neos.Foo).bar'));

        $expected = [
            0 =>  'b-lah',
            1 => 'asdf',
            2 => 'prototype(Neos.Foo)',
            3 => 'b-ar'
        ];
        self::assertSame($expected, preg_split($pattern, 'b-lah.asdf.prototype(Neos.Foo).b-ar'));

        $expected = [
            0 =>  'b:lah',
            1 => 'asdf',
            2 => 'prototype(Neos.Foo)',
            3 => 'b:ar'
        ];
        self::assertSame($expected, preg_split($pattern, 'b:lah.asdf.prototype(Neos.Foo).b:ar'));
    }

    /**
     * @test
     */
    public function testSPLIT_PATTERN_OBJECTDEFINITION()
    {
        $pattern = Parser::SPLIT_PATTERN_OBJECTDEFINITION;

        $expected = [
            0 => 'foo.bar = Test',
            'ObjectPath' => 'foo.bar',
            1 => 'foo.bar',
            'Operator' => '=',
            2 => '=',
            'Value' => 'Test',
            3 => 'Test'
        ];
        $this->assertRegexMatches('foo.bar = Test', $pattern, $expected, 'Simple assignment');

        $expected = [
            0 => 'foo.\'@bar\' = Test',
            'ObjectPath' => 'foo.\'@bar\'',
            1 => 'foo.\'@bar\'',
            'Operator' => '=',
            2 => '=',
            'Value' => 'Test',
            3 => 'Test'
        ];
        $this->assertRegexMatches('foo.\'@bar\' = Test', $pattern, $expected, 'Simple assignment with single quoted key');

        $expected = [
            0 => 'foo."@bar" = Test',
            'ObjectPath' => 'foo."@bar"',
            1 => 'foo."@bar"',
            'Operator' => '=',
            2 => '=',
            'Value' => 'Test',
            3 => 'Test'
        ];
        $this->assertRegexMatches('foo."@bar" = Test', $pattern, $expected, 'Simple assignment with double quoted key');

        $expected = [
            0 => 'foo.prototype(Neos.Blah).bar = Test',
            'ObjectPath' => 'foo.prototype(Neos.Blah).bar',
            1 => 'foo.prototype(Neos.Blah).bar',
            'Operator' => '=',
            2 => '=',
            'Value' => 'Test',
            3 => 'Test'
        ];
        $this->assertRegexMatches('foo.prototype(Neos.Blah).bar = Test', $pattern, $expected, 'Prototype Object assignment');

        $expected = [
            0 => 'prototype(Neos.Blah).bar = Test',
            'ObjectPath' => 'prototype(Neos.Blah).bar',
            1 => 'prototype(Neos.Blah).bar',
            'Operator' => '=',
            2 => '=',
            'Value' => 'Test',
            3 => 'Test'
        ];
        $this->assertRegexMatches('prototype(Neos.Blah).bar = Test', $pattern, $expected, 'Prototype Object assignment at root object');

        $expected = [
        ];
        $this->assertRegexMatches('prototype(Neos.Blah) {', $pattern, $expected, 'Prototype Object assignment at root object');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE()
    {
        $pattern = Parser::SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE;
        self::assertEquals(preg_match($pattern, 'prototype(asf.Ds:1)'), 1, 'The SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE pattern did not match (1).');
        self::assertEquals(preg_match($pattern, 'prototype(Neos.Flow:Test)'), 1, 'The SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE pattern did not match (2).');
        self::assertEquals(preg_match($pattern, 'message'), 0, 'The SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE pattern matched(3).');
    }

    /**
     * Checks the regular expression SPLIT_PATTERN_VALUENUMBER
     *
     * @test
     */
    public function testSPLIT_PATTERN_VALUENUMBER()
    {
        $pattern = Parser::SPLIT_PATTERN_VALUENUMBER;
        self::assertEquals(preg_match($pattern, ' 1'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match a number with a space in front.');
        self::assertEquals(preg_match($pattern, '12221'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match the number 12221.');
        self::assertEquals(preg_match($pattern, '-12'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match a negative number.');
        self::assertEquals(preg_match($pattern, ' -42'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match a negative number with a space in front.');
        self::assertEquals(preg_match($pattern, '-12.5'), 0, 'The SPLIT_PATTERN_VALUENUMBER pattern matched a negative float number.');
        self::assertEquals(preg_match($pattern, '42.5'), 0, 'The SPLIT_PATTERN_VALUENUMBER pattern matched a positive float number.');
    }

    /**
     * Checks the regular expression SPLIT_PATTERN_VALUEMULTILINELITERAL
     *
     * @test
     */
    public function testSPLIT_PATTERN_VALUEMULTILINELITERAL()
    {
        $pattern = Parser::SPLIT_PATTERN_VALUEMULTILINELITERAL;
        self::assertEquals(preg_match($pattern, "\${'col-sm-'+"), 0, 'This should not match; but it does');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_VALUEOBJECTTYPE()
    {
        $pattern = Parser::SCAN_PATTERN_VALUEOBJECTTYPE;

        self::assertEquals(1, preg_match($pattern, 'Neos.Fusion:Foo'), 'It did not match a simple TS Object Type');
        self::assertEquals(1, preg_match($pattern, 'Foo'), 'It matched an unqualified TS Object Type');

        $expected = [
            0 => 'Foo',
            'namespace' => '',
            1 => '',
            'unqualifiedType' => 'Foo',
            2 => 'Foo'
        ];
        $this->assertRegexMatches('Foo', $pattern, $expected, 'Detailed result');

        $expected = [
            0 => 'Neos.Fusion:Foo',
            'namespace' => 'Neos.Fusion',
            1 => 'Neos.Fusion',
            'unqualifiedType' => 'Foo',
            2 => 'Foo'
        ];
        $this->assertRegexMatches('Neos.Fusion:Foo', $pattern, $expected, 'Detailed result');
    }

    public function SPLIT_PATTERN_COMMENTTYPEdataProvider()
    {
        return [
            'hashComment' => [
                'tsSnippet' => '# */asdf',
                'expectedCommentToken' => '#'
            ],
            'doubleSlashComment' => [
                'tsSnippet' => '// comment with */ and more comment',
                'expectedCommentToken' => '//'
            ],
            'slashStarComment' => [
                'tsSnippet' => '/* comment with // and more comment */',
                'expectedCommentToken' => '/*'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider SPLIT_PATTERN_COMMENTTYPEdataProvider
     * @parameter $markdownMessage
     * @parameter $renderedMessage
     */
    public function testSPLIT_PATTERN_COMMENTTYPE($tsSnippet, $expectedCommentToken)
    {
        $pattern = Parser::SPLIT_PATTERN_COMMENTTYPE;

        self::assertEquals(1, preg_match($pattern, $tsSnippet), 'It did not match a complex TS comment.');

        $expected = [
            0 => $tsSnippet,
            1 => $expectedCommentToken
        ];
        $this->assertRegexMatches($tsSnippet, $pattern, $expected, 'It did not match comment-parts as expected.');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_DSL_EXPRESSION_START()
    {
        $pattern = Parser::SCAN_PATTERN_DSL_EXPRESSION_START;
        self::assertEquals(preg_match($pattern, 'dsl`value`'), 1, 'The SCAN_PATTERN_DSL_EXPRESSION_START match a single line dsl-expression.');
        self::assertEquals(preg_match($pattern, 'dsl`line 1' . chr(10) . 'line 2' .chr(10) . 'line 3`'), 1, 'The SCAN_PATTERN_DSL_EXPRESSION_START match a multiline dsl-expression.');
        self::assertEquals(preg_match($pattern, 'true'), 0, 'The SCAN_PATTERN_DSL_EXPRESSION_START does not match a boolean assignment.');
        self::assertEquals(preg_match($pattern, '1234'), 0, 'The SCAN_PATTERN_DSL_EXPRESSION_START does not match a integer assignment.');
        self::assertEquals(preg_match($pattern, '\'string\''), 0, 'The SCAN_PATTERN_DSL_EXPRESSION_START does not match a string assignment.');
        self::assertEquals(preg_match($pattern, '${Math.random()}'), 0, 'The SCAN_PATTERN_DSL_EXPRESSION_START does not match an eel assignment.');
        self::assertEquals(preg_match($pattern, 'Neos.Fusion:Value'), 0, 'The SCAN_PATTERN_DSL_EXPRESSION_START does not match an object assignment.');
        self::assertEquals(preg_match($pattern, 'Neos.Fusion:Value {'), 0, 'The SCAN_PATTERN_DSL_EXPRESSION_START does not match an object assignment.');
    }

    public function SPLIT_PATTERN_DSL_EXPRESSIONdataProvider()
    {
        return [
            'singleLineDsl' => [
                'expression' => 'testDsl`testDslExpression`',
                'dslIdentifier' => 'testDsl',
                '$dslCode' => 'testDslExpression'
            ],
            'multilineDsl' => [
                'expression' => 'testDsl`line 1' . chr(10) . 'line 2' . chr(10) . 'line 3`',
                'dslIdentifier' => 'testDsl',
                '$dslCode' => 'line 1' . chr(10) . 'line 2' . chr(10) . 'line 3'
            ],
            'dslWithSpecialCharacters' => [
                'expression' => 'testDsl`${}()[]@<>/123456789abdefg`',
                'dslIdentifier' => 'testDsl',
                '$dslCode' => '${}()[]@<>/123456789abdefg'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider SPLIT_PATTERN_DSL_EXPRESSIONdataProvider
     * @parameter $markdownMessage
     * @parameter $renderedMessage
     */
    public function testSPLIT_PATTERN_DSL_EXPRESSION($expression, $dslIdentidier, $dslCode)
    {
        $pattern = Parser::SPLIT_PATTERN_DSL_EXPRESSION;
        $expected = [
            0 => $expression,
            'identifier' => $dslIdentidier,
            1  => $dslIdentidier,
            'code' => $dslCode,
            2 => $dslCode
        ];
        $this->assertRegexMatches($expression, $pattern, $expected, 'It did not match dsl-parts as expected.');
    }

    /**
     * Custom assertion for matching regexes
     *
     * @param $testString
     * @param $pattern
     * @param $expectedMatches
     * @param $explanation
     */
    protected function assertRegexMatches($testString, $pattern, $expectedMatches, $explanation)
    {
        $matches = [];
        preg_match($pattern, $testString, $matches);

        self::assertSame($expectedMatches, $matches, $explanation);
    }
}
