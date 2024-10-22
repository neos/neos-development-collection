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
        $actualAst = $parser->parseFromSource(\Neos\Fusion\Core\FusionSourceCodeCollection::fromString('value = TestPassthroughDsl`"StringExpressionValue"`'))->toArray();
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
        $actualAst = $parser->parseFromSource(Fusion\Core\FusionSourceCodeCollection::fromString('value = TestPassthroughDsl`"String' . "\n" . 'Expression' . "\n" . 'Value"`'))->toArray();
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

        $actualAst = $parser->parseFromSource(\Neos\Fusion\Core\FusionSourceCodeCollection::fromString('value = TestPassthroughDsl`true`'))->toArray();
        $expectedAst = [
            'value' => true
        ];
        self::assertEquals($expectedAst, $actualAst);

        $actualAst = $parser->parseFromSource(\Neos\Fusion\Core\FusionSourceCodeCollection::fromString('value = TestPassthroughDsl`false`'))->toArray();
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

        $actualAst = $parser->parseFromSource(\Neos\Fusion\Core\FusionSourceCodeCollection::fromString('value = TestPassthroughDsl`1234`'))->toArray();
        $expectedAst = [
            'value' => 1234
        ];
        self::assertEquals($expectedAst, $actualAst);

        $actualAst = $parser->parseFromSource(\Neos\Fusion\Core\FusionSourceCodeCollection::fromString('value = TestPassthroughDsl`12.34`'))->toArray();
        $expectedAst = [
            'value' => 12.34
        ];
        self::assertEquals($expectedAst, $actualAst);

        $actualAst = $parser->parseFromSource(\Neos\Fusion\Core\FusionSourceCodeCollection::fromString('value = TestPassthroughDsl`-12.34`'))->toArray();
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
        $actualAst = $parser->parseFromSource(\Neos\Fusion\Core\FusionSourceCodeCollection::fromString('value = TestPassthroughDsl`${1234}`'))->toArray();
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
        $actualAst = $parser->parseFromSource(\Neos\Fusion\Core\FusionSourceCodeCollection::fromString('value = TestFusionObjectDsl`{"objectName": "Neos.Fusion:Value", "attributes": { "value": "foo" }}`'))->toArray();
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
        $this->expectExceptionCode(1180600696);
        $parser->parseFromSource(\Neos\Fusion\Core\FusionSourceCodeCollection::fromString('value = TestUnknownDsl`foobar`'))->toArray();
    }

    /**
     * @test
     */
    public function parserThrowsExceptionIfAnDslExprssionIsNotClosed()
    {
        $parser = new Parser();
        $this->expectException(Fusion\Exception::class);
        $this->expectExceptionCode(1490714685);
        $parser->parseFromSource(\Neos\Fusion\Core\FusionSourceCodeCollection::fromString('value = TestPassthroughDsl`foobar'))->toArray();
    }
}
