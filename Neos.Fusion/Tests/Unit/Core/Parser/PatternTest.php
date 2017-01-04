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
        $this->assertEquals(preg_match($pattern, '/* This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a block comment start.');
        $this->assertEquals(preg_match($pattern, '# This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a hash comment start.');
        $this->assertEquals(preg_match($pattern, '// This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a double slash comment start.');
        $this->assertEquals(preg_match($pattern, ' # This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a hash comment start with preceeding whitespace.');
        $this->assertEquals(preg_match($pattern, '/ This is not a comment start ...'), 0, 'The SCAN_PATTERN_COMMENT pattern matched a single slash.');
        $this->assertEquals(preg_match($pattern, '*/ This is not a comment start ...'), 0, 'The SCAN_PATTERN_COMMENT pattern matched a comment block ending.');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_OPENINGCONFINEMENT()
    {
        $pattern = Parser::SCAN_PATTERN_OPENINGCONFINEMENT;
        $this->assertEquals(preg_match($pattern, 'foo.bar.baz {'), 1, 'a confinement was not matched');
        $this->assertEquals(preg_match($pattern, 'fo-o.bar-la.baz {'), 1, 'a confinement with dashes was not matched');
        $this->assertEquals(preg_match($pattern, 'fo:o.bar:la.baz {'), 1, 'a confinement with colons was not matched');
        $this->assertEquals(preg_match($pattern, 'f21oo.b12ar.baz {'), 1, 'a confinement with a number was not matched');
        $this->assertEquals(preg_match($pattern, '		foo.bar.baz	    {	'), 1, 'a path which contained numerous whitespace was not matched');
        $this->assertEquals(preg_match($pattern, 'f21oo.b12ar.baz { foo'), 0, 'a confinement with parts after the opening confinement matched');
        $this->assertEquals(preg_match($pattern, '1foo.bar.baz {'), 1, 'a path which contained a number was matched (1)');
        $this->assertEquals(preg_match($pattern, 'foo.1bar.baz {'), 1, 'a path which contained a number was matched (2)');
        $this->assertEquals(preg_match($pattern, 'foo.1bar.\'b@r\' {'), 1, 'a path which contained a single quoted key was matched (1)');
        $this->assertEquals(preg_match($pattern, 'foo.\'1b@r\'.\'b@r\' {'), 1, 'a path which contained a single quoted key was matched (2)');
        $this->assertEquals(preg_match($pattern, 'foo.1bar."b@r" {'), 1, 'a path which contained a double quoted key was matched (1)');
        $this->assertEquals(preg_match($pattern, 'foo."1b@r"."b@r" {'), 1, 'a path which contained a double quoted key was matched (2)');
        $this->assertEquals(preg_match($pattern, 'foo."1b@r".\'b@r\' {'), 1, 'a path which contained a single & double quoted keys was matched');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_CLOSINGCONFINEMENT()
    {
        $pattern = Parser::SCAN_PATTERN_CLOSINGCONFINEMENT;
        $this->assertEquals(preg_match($pattern, '}'), 1, 'a closing confinement was not matched');
        $this->assertEquals(preg_match($pattern, '		  }'), 1, 'a closing confinement with leading whitespace was not matched');
        $this->assertEquals(preg_match($pattern, '		  }     '), 1, 'a closing confinement with leading and following whitespace was not matched');
        $this->assertEquals(preg_match($pattern, '		  }    assas '), 0, 'a closing confinement with following text was matched, although it should not.');
    }

    /**
     * Checks the regular expression SCAN_PATTERN_DECLARATION
     *
     * @test
     */
    public function testSCAN_PATTERN_DECLARATION()
    {
        $pattern = Parser::SCAN_PATTERN_DECLARATION;
        $this->assertEquals(preg_match($pattern, 'include : source = "resource"'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an include declaration.');
        $this->assertEquals(preg_match($pattern, 'include:source = "resource"'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an include declaration without whitespaces.');
        $this->assertEquals(preg_match($pattern, 'namespace: cms = Test'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an namespace declaration.');
        $this->assertEquals(preg_match($pattern, '  namespace: cms = Test'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an namespace declaration whith leading whitespace.');
        $this->assertEquals(preg_match($pattern, 'ASDF  namespace: cms = Test'), 0, 'The SCAN_PATTERN_DECLARATION pattern did match an namespace declaration whith leading text.');
        $this->assertEquals(preg_match($pattern, 'ASDF  namespace: Neos.Neos = Foo'), 0, 'The SCAN_PATTERN_DECLARATION pattern did match an namespace declaration whith leading text.');
        $this->assertEquals(preg_match($pattern, '// This is a comment ...'), 0, 'The SCAN_PATTERN_DECLARATION pattern matched a comment.');
    }

    /**
     * Checks the regular expression SCAN_PATTERN_OBJECTDEFINITION
     *
     * @test
     */
    public function testSCAN_PATTERN_OBJECTDEFINITION()
    {
        $pattern = Parser::SCAN_PATTERN_OBJECTDEFINITION;
        $this->assertEquals(preg_match($pattern, 'myObject = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment.');
        $this->assertEquals(preg_match($pattern, '  myObject = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment with leading whitespace.');
        $this->assertEquals(preg_match($pattern, 'myObject.content = "stuff"'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match a literal assignment of a property.');
        $this->assertEquals(preg_match($pattern, 'my-object.con-tent = "stuff"'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match a dasherized path.');
        $this->assertEquals(preg_match($pattern, 'my:object.con:tent = "stuff"'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match a colonrized path.');
        $this->assertEquals(preg_match($pattern, 'myObject.10 = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment of a content array item.');
        $this->assertEquals(preg_match($pattern, 'myObject.\'b@r\' = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment of a single quoted key.');
        $this->assertEquals(preg_match($pattern, 'myObject."b@r" = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment of a double quoted key.');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_OBJECTPATH()
    {
        $pattern = Parser::SCAN_PATTERN_OBJECTPATH;
        $this->assertEquals(preg_match($pattern, 'foo.bar'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match a simple object path (1)');
        $this->assertEquals(preg_match($pattern, 'foo.\'b@r\''), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match a object path with a single quoted key');
        $this->assertEquals(preg_match($pattern, 'foo."b@r"'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match a object path with a double quoted key');
        $this->assertEquals(preg_match($pattern, 'foo.prototype(TYPO3.Foo).bar'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match an object path with a prototype definition inside (2)');
        $this->assertEquals(preg_match($pattern, 'prototype(TYPO3.Foo)'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match an object path which consists only of a prototype definition (3)');
        $this->assertEquals(preg_match($pattern, 'foo.bar.10.baz'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match a simple object path (4)');
        $this->assertEquals(preg_match($pattern, 'foo.bar.as10.baz'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did not match a simple object path (5)');
        $this->assertEquals(preg_match($pattern, '12foo.bar.as.baz'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did match a simple object path (6)');
        $this->assertEquals(preg_match($pattern, '12f-o-o.ba-r.as.ba-z'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did match a simple object path with dashes (7)');
        $this->assertEquals(preg_match($pattern, '12f:o:o.ba:r.as.ba:z'), 1, 'The SCAN_PATTERN_OBJECTPATH pattern did match a simple object path with colons (7)');
    }

    /**
     * @test
     */
    public function testSPLIT_PATTERN_OBJECTPATH()
    {
        $pattern = Parser::SPLIT_PATTERN_OBJECTPATH;

        $expected = array(
            0 => 'foo',
            1 => 'bar'
        );
        $this->assertSame($expected, preg_split($pattern, 'foo.bar'));

        $expected = array(
            0 => 'prototype(TYPO3.Foo)',
            1 => 'bar'
        );
        $this->assertSame($expected, preg_split($pattern, 'prototype(TYPO3.Foo).bar'));

        $expected = array(
            0 => 'asdf',
            1 => 'prototype(TYPO3.Foo)',
            2 => 'bar'
        );
        $this->assertSame($expected, preg_split($pattern, 'asdf.prototype(TYPO3.Foo).bar'));

        $expected = array(
            0 =>  'blah',
            1 => 'asdf',
            2 => 'prototype(TYPO3.Foo)',
            3 => 'bar'
        );
        $this->assertSame($expected, preg_split($pattern, 'blah.asdf.prototype(TYPO3.Foo).bar'));

        $expected = array(
            0 =>  'b-lah',
            1 => 'asdf',
            2 => 'prototype(TYPO3.Foo)',
            3 => 'b-ar'
        );
        $this->assertSame($expected, preg_split($pattern, 'b-lah.asdf.prototype(TYPO3.Foo).b-ar'));

        $expected = array(
            0 =>  'b:lah',
            1 => 'asdf',
            2 => 'prototype(TYPO3.Foo)',
            3 => 'b:ar'
        );
        $this->assertSame($expected, preg_split($pattern, 'b:lah.asdf.prototype(TYPO3.Foo).b:ar'));
    }

    /**
     * @test
     */
    public function testSPLIT_PATTERN_OBJECTDEFINITION()
    {
        $pattern = Parser::SPLIT_PATTERN_OBJECTDEFINITION;

        $expected = array(
            0 => 'foo.bar = Test',
            'ObjectPath' => 'foo.bar',
            1 => 'foo.bar',
            'Operator' => '=',
            2 => '=',
            'Value' => 'Test',
            3 => 'Test'
        );
        $this->assertRegexMatches('foo.bar = Test', $pattern, $expected, 'Simple assignment');

        $expected = array(
            0 => 'foo.\'@bar\' = Test',
            'ObjectPath' => 'foo.\'@bar\'',
            1 => 'foo.\'@bar\'',
            'Operator' => '=',
            2 => '=',
            'Value' => 'Test',
            3 => 'Test'
        );
        $this->assertRegexMatches('foo.\'@bar\' = Test', $pattern, $expected, 'Simple assignment with single quoted key');

        $expected = array(
            0 => 'foo."@bar" = Test',
            'ObjectPath' => 'foo."@bar"',
            1 => 'foo."@bar"',
            'Operator' => '=',
            2 => '=',
            'Value' => 'Test',
            3 => 'Test'
        );
        $this->assertRegexMatches('foo."@bar" = Test', $pattern, $expected, 'Simple assignment with double quoted key');

        $expected = array(
            0 => 'foo.prototype(TYPO3.Blah).bar = Test',
            'ObjectPath' => 'foo.prototype(TYPO3.Blah).bar',
            1 => 'foo.prototype(TYPO3.Blah).bar',
            'Operator' => '=',
            2 => '=',
            'Value' => 'Test',
            3 => 'Test'
        );
        $this->assertRegexMatches('foo.prototype(TYPO3.Blah).bar = Test', $pattern, $expected, 'Prototype Object assignment');

        $expected = array(
            0 => 'prototype(TYPO3.Blah).bar = Test',
            'ObjectPath' => 'prototype(TYPO3.Blah).bar',
            1 => 'prototype(TYPO3.Blah).bar',
            'Operator' => '=',
            2 => '=',
            'Value' => 'Test',
            3 => 'Test'
        );
        $this->assertRegexMatches('prototype(TYPO3.Blah).bar = Test', $pattern, $expected, 'Prototype Object assignment at root object');

        $expected = array(
        );
        $this->assertRegexMatches('prototype(TYPO3.Blah) {', $pattern, $expected, 'Prototype Object assignment at root object');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE()
    {
        $pattern = Parser::SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE;
        $this->assertEquals(preg_match($pattern, 'prototype(asf.Ds:1)'), 1, 'The SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE pattern did not match (1).');
        $this->assertEquals(preg_match($pattern, 'prototype(Neos.Flow:Test)'), 1, 'The SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE pattern did not match (2).');
        $this->assertEquals(preg_match($pattern, 'message'), 0, 'The SCAN_PATTERN_OBJECTPATHSEGMENT_IS_PROTOTYPE pattern matched(3).');
    }

    /**
     * Checks the regular expression SPLIT_PATTERN_VALUENUMBER
     *
     * @test
     */
    public function testSPLIT_PATTERN_VALUENUMBER()
    {
        $pattern = Parser::SPLIT_PATTERN_VALUENUMBER;
        $this->assertEquals(preg_match($pattern, ' 1'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match a number with a space in front.');
        $this->assertEquals(preg_match($pattern, '12221'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match the number 12221.');
        $this->assertEquals(preg_match($pattern, '-12'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match a negative number.');
        $this->assertEquals(preg_match($pattern, ' -42'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match a negative number with a space in front.');
        $this->assertEquals(preg_match($pattern, '-12.5'), 0, 'The SPLIT_PATTERN_VALUENUMBER pattern matched a negative float number.');
        $this->assertEquals(preg_match($pattern, '42.5'), 0, 'The SPLIT_PATTERN_VALUENUMBER pattern matched a positive float number.');
    }

    /**
     * Checks the regular expression SPLIT_PATTERN_VALUEMULTILINELITERAL
     *
     * @test
     */
    public function testSPLIT_PATTERN_VALUEMULTILINELITERAL()
    {
        $pattern = Parser::SPLIT_PATTERN_VALUEMULTILINELITERAL;
        $this->assertEquals(preg_match($pattern, "\${'col-sm-'+"), 0, 'This should not match; but it does');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_VALUEOBJECTTYPE()
    {
        $pattern = Parser::SCAN_PATTERN_VALUEOBJECTTYPE;

        $this->assertEquals(1, preg_match($pattern, 'Neos.Fusion:Foo'), 'It did not match a simple TS Object Type');
        $this->assertEquals(1, preg_match($pattern, 'Foo'), 'It matched an unqualified TS Object Type');

        $expected = array(
            0 => 'Foo',
            'namespace' => '',
            1 => '',
            'unqualifiedType' => 'Foo',
            2 => 'Foo'
        );
        $this->assertRegexMatches('Foo', $pattern, $expected, 'Detailed result');

        $expected = array(
            0 => 'Neos.Fusion:Foo',
            'namespace' => 'Neos.Fusion',
            1 => 'Neos.Fusion',
            'unqualifiedType' => 'Foo',
            2 => 'Foo'
        );
        $this->assertRegexMatches('Neos.Fusion:Foo', $pattern, $expected, 'Detailed result');
    }

    public function SPLIT_PATTERN_COMMENTTYPEdataProvider()
    {
        return array(
            'hashComment' => array(
                'tsSnippet' => '# */asdf',
                'expectedCommentToken' => '#'
            ),
            'doubleSlashComment' => array(
                'tsSnippet' => '// comment with */ and more comment',
                'expectedCommentToken' => '//'
            ),
            'slashStarComment' => array(
                'tsSnippet' => '/* comment with // and more comment */',
                'expectedCommentToken' => '/*'
            )
        );
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

        $this->assertEquals(1, preg_match($pattern, $tsSnippet), 'It did not match a complex TS comment.');

        $expected = array(
            0 => $tsSnippet,
            1 => $expectedCommentToken
        );
        $this->assertRegexMatches($tsSnippet, $pattern, $expected, 'It did not match comment-parts as expected.');
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
        $matches = array();
        preg_match($pattern, $testString, $matches);

        $this->assertSame($expectedMatches, $matches, $explanation);
    }
}
