<?php
namespace Neos\Fusion\Tests\Functional\Parser;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Fusion\Core\Parser;
use Neos\Fusion;

/**
 * Testcase for the Fusion Parser
 */
class FusionParserTest extends FunctionalTestCase
{

    /**
     * @test
     */
    public function parserHandlesExpressionsThatReturnStrings()
    {
        $parser = new Parser();
        $actualAst = $parser->parse('value = TestPassthroughDsl`"StringExpressionValue"`');
        $expectedAst = [
            'value' => 'StringExpressionValue'
        ];
        $this->assertEquals($expectedAst, $actualAst);
    }

    /**
     * @test
     */
    public function parserHandlesExpressionsThatReturnMultilineStrings()
    {
        $parser = new Parser();
        $actualAst = $parser->parse('value = TestPassthroughDsl`"String' . chr(10) . 'Expression' . chr(10) . 'Value"`');
        $expectedAst = [
            'value' => 'String' . chr(10) . 'Expression' . chr(10) . 'Value'
        ];
        $this->assertEquals($expectedAst, $actualAst);
    }

    /**
     * @test
     */
    public function parserHandlesDslExpressionThatReturnsBooleans()
    {
        $parser = new Parser();

        $actualAst = $parser->parse('value = TestPassthroughDsl`true`');
        $expectedAst = [
            'value' => true
        ];
        $this->assertEquals($expectedAst, $actualAst);

        $actualAst = $parser->parse('value = TestPassthroughDsl`false`');
        $expectedAst = [
            'value' => false
        ];
        $this->assertEquals($expectedAst, $actualAst);
    }

    /**
     * @test
     */
    public function parserHandlesDslExpressionThatReturnsNumbers()
    {
        $parser = new Parser();

        $actualAst = $parser->parse('value = TestPassthroughDsl`1234`');
        $expectedAst = [
            'value' => 1234
        ];
        $this->assertEquals($expectedAst, $actualAst);

        $actualAst = $parser->parse('value = TestPassthroughDsl`12.34`');
        $expectedAst = [
            'value' => 12.34
        ];
        $this->assertEquals($expectedAst, $actualAst);

        $actualAst = $parser->parse('value = TestPassthroughDsl`-12.34`');
        $expectedAst = [
            'value' => -12.34
        ];
        $this->assertEquals($expectedAst, $actualAst);
    }

    /**
     * @test
     */
    public function parserHandlesDslExpressionThatReturnsEelExpressions()
    {
        $parser = new Parser();
        $actualAst = $parser->parse('value = TestPassthroughDsl`${1234}`');
        $expectedAst = [
            'value' => ["__eelExpression" => "1234","__value" => null, "__objectType" => null]
        ];
        $this->assertEquals($expectedAst, $actualAst);
    }

    /**
     * @test
     */
    public function parserHandlesDslExpressionThatReturnsFusionObjects()
    {
        $parser = new Parser();
        $actualAst = $parser->parse('value = TestValueObjectDsl`foo`');
        $expectedAst = [
            'value' => ["__eelExpression" => null,"__value" => null, "__objectType" => 'Neos.Fusion:Value', 'value' => "foo"]
        ];
        $this->assertEquals($expectedAst, $actualAst);
    }

    /**
     * @test
     */
    public function parserThrowsExceptionIfAnUnknownDslIsExecuted()
    {
        $parser = new Parser();
        $this->expectException(Fusion\Exception::class);
        $this->expectExceptionCode(1490776550);
        $parser->parse('value = TestUnknownDsl`foobar`');
    }

    /**
     * @test
     */
    public function parserThrowsExceptionIfAnDslExprssionIsNotClosed()
    {
        $parser = new Parser();
        $this->expectException(Fusion\Exception::class);
        $this->expectExceptionCode(1490714685);
        $parser->parse('value = TestPassthroughDsl`foobar');
    }
}
