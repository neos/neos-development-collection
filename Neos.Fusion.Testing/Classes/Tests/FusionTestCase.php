<?php

namespace Neos\Fusion\Testing\Tests;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Testing\Service\GenerateTestCases;
use Neos\Fusion\Testing\Service\TestCaseRunner;
use PHPUnit\Framework\TestCase;

/*
 * get all first level paths which end with 'Tests' or which are a Neos.Fusion.Testing:TestCase
 * it can have multiple test and fusion data providers - which will be in mixed combinations evaluated.
 *
 * return values of @beforeRender and @afterRender are ignored, their purpose is to call methods on the context objects:
 * - self - the instance of this testcase
 * - view - the instance of the used view
 * - result - return value of the provider accessible in @afterRender
 *
 * baseTest.whatImTestingHere {
 *   @test.1 {
 *     @beforeRender = ${ view.assign('foo', 123) && self.expectException('Foo\Class') }
 *   }
 *   @test.2 {
 *     @beforeRender = ${ view.assign('foo', 'hi') }
 *     @afterRender = ${ self.assertSame(actual, 'hi') }
 *   }
 *
 *   @fusion.1 = Foo:BarWhichTrowsWhenAnIntGivenElseReturns {
 *     foo = ${ foo }
 *   }
 * }
 */
abstract class FusionTestCase extends TestCase implements ProtectedContextAwareInterface
{
    /**
     * @api Define your root fusion via implementing this abstract method.
     * @return string
     */
    abstract public static function getFixturesRootFusion(): string;

    public function generateTestCasesFromFusionAst()
    {
        $fusion = file_get_contents(static::getFixturesRootFusion());

        $parsedFusion = (new Parser())->parse($fusion, static::getFixturesRootFusion());

        yield from GenerateTestCases::buildFromFirstLevelFusionTestObjects($parsedFusion);
    }

    /**
     * @dataProvider generateTestCasesFromFusionAst
     * @test
     */
    public function execute(string $fusionRenderPath, ?string $beforeHookPath, ?string $afterHookPath)
    {
        TestCaseRunner::assertFusionRendersAsExpected($this, static::getFixturesRootFusion(), $fusionRenderPath, $beforeHookPath, $afterHookPath);
    }

    /**
     * so that ${ self.assertTrue(true) works in eel }
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
