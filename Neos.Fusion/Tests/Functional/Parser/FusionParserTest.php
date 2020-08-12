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
        self::assertEquals($expectedAst, $actualAst);
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
        self::assertEquals($expectedAst, $actualAst);
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
        self::assertEquals($expectedAst, $actualAst);

        $actualAst = $parser->parse('value = TestPassthroughDsl`false`');
        $expectedAst = [
            'value' => false
        ];
        self::assertEquals($expectedAst, $actualAst);
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
        self::assertEquals($expectedAst, $actualAst);

        $actualAst = $parser->parse('value = TestPassthroughDsl`12.34`');
        $expectedAst = [
            'value' => 12.34
        ];
        self::assertEquals($expectedAst, $actualAst);

        $actualAst = $parser->parse('value = TestPassthroughDsl`-12.34`');
        $expectedAst = [
            'value' => -12.34
        ];
        self::assertEquals($expectedAst, $actualAst);
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
        self::assertEquals($expectedAst, $actualAst);
    }

    /**
     * @test
     */
    public function parserHandlesDslExpressionThatReturnsFusionObjects()
    {
        $parser = new Parser();
        $actualAst = $parser->parse('value = TestFusionObjectDsl`{"objectName": "Neos.Fusion:Value", "attributes": { "value": "foo" }}`');
        $expectedAst = [
            'value' => ["__eelExpression" => null,"__value" => null, "__objectType" => 'Neos.Fusion:Value', 'value' => "foo"]
        ];
        self::assertEquals($expectedAst, $actualAst);
    }

    /**
     * @test
     */
    public function parserHandlesDslExpressionThatReturnsFusionObjectsInNamespace()
    {
        $parser = new Parser();
        $actualAst = $parser->parse(
            '
            namespace: F=Neos.Fusion
            value = TestFusionObjectDsl`{"objectName": "F:Value", "attributes": { "value": "foo" }}`'
        );
        $expectedAst = [
            'value' => ["__eelExpression" => null,"__value" => null, "__objectType" => 'Neos.Fusion:Value', 'value' => "foo"]
        ];
        self::assertEquals($expectedAst, $actualAst);
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
