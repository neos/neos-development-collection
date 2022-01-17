<?php

namespace Neos\Fusion\Tests\Functional\FusionObjects\FusionTestsFusion;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\Parser;
use PHPUnit\Framework\TestCase;

/*
 * get all first level paths which end with 'Tests'
 * the following nested key will be the 'test function'
 * it can have multiple scenarios and providers - which will be in all combinations evaluated.
 *
 * return values of @before and @after are ignored, their purpose is to call methods on the context objects:
 * - self - the instance of this testcase
 * - view - the instance of the used view
 * - actual - return value of the provider accessible in @after
 *
 * baseTest.whatImTestingHere {
 *   @scenario.1 {
 *     @before = ${ view.assign('foo', 123) && self.expectException('Foo\Class') }
 *   }
 *   @scenario.2 {
 *     @before = ${ view.assign('foo', 'hi') }
 *     @after = ${ self.assertSame(actual, 'hi') }
 *   }
 *
 *   @provider.1 = Foo:BarWhichTrowsWhenAnIntGivenElseReturns {
 *     foo = ${ foo }
 *   }
 * }
 */
class FusionTestsFusionTest extends TestCase implements ProtectedContextAwareInterface
{
    protected const ROOT_FUSION_FIXTURE = __DIR__ . '/../Fixtures/Fusion/Root.fusion';

    protected const FIXTURES_DIR = __DIR__ . '/../Fixtures';

    public function generateTestCasesFromFusionAst()
    {
        $fusion = file_get_contents(self::ROOT_FUSION_FIXTURE);

        // todo ast should be reused in view, to not parse twice
        $parsedFusion = (new Parser())->parse($fusion, self::ROOT_FUSION_FIXTURE);

        foreach ($parsedFusion as $testCase => $testCaseAst) {
            // get all first level paths that end with 'Test'
            if (is_string($testCase) === false
                || substr($testCase, -4) !== 'Test') {
                continue;
            }

            // show no mercy
            if (is_array($testCaseAst) === false) {
                throw new \Exception();
            }

            // each nested path of the 'TestCase' will be run as test.
            /*
             * rendererTest.$testName {
             *     @scenario.1 {}
             *     @provider.1 {}
             * }
             *
             */
            foreach ($testCaseAst as $testName => ['__meta' => $testConfiguration]) {
                $testConfigurationProvider = $testConfiguration['provider'];
                $testConfigurationScenarios = $testConfiguration['scenario'];

                $providerFusionPaths = [];
                foreach ($testConfigurationProvider as $providerName => $_) {
                    $providerFusionPaths[$providerName] = "$testCase/$testName/__meta/provider/$providerName";
                }

                foreach ($testConfigurationScenarios as $scenario => ['__meta' => $scenarioConfiguration]) {

                    $scenarioConfigurationFusionPath = "$testCase/$testName/__meta/scenario/$scenario/__meta";
                    $beforeHookFusionPath = isset($scenarioConfiguration['before']) ? $scenarioConfigurationFusionPath . "/before" : null;
                    $afterHookFusionPath = isset($scenarioConfiguration['after']) ? $scenarioConfigurationFusionPath . "/after" : null;

                    foreach ($providerFusionPaths as $providerName => $fusionPath) {
                        yield "$testCase.$testName with: scenario[$scenario] provider[$providerName]" => [
                            $fusionPath,
                            $beforeHookFusionPath,
                            $afterHookFusionPath
                        ];
                    }
                }
            }
        }
    }

    /**
     * @dataProvider generateTestCasesFromFusionAst
     * @test
     */
    public function assertFusionRendersAsExpected(string $renderPath, ?string $before, ?string $after)
    {
        $view = new FusionViewAllowedContext();

        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();

        $view->setControllerContext($controllerContext);
        $view->setPackageKey('Neos.Fusion');
        // todo prevent reparsing Fusion.
        $view->setFusionPathPattern(self::ROOT_FUSION_FIXTURE);

        $view->assign('fixtureDirectory', self::FIXTURES_DIR)
            ->assign('self', $this)
            ->assign('view', $view);

        // @before eel hook
        if (isset($before)) {
            $view->setFusionPath($before);
            $view->render();
        }

        $view->setFusionPath($renderPath);
        $result = $view->render();

        $view->assign('actual', $result);

        // @after eel hook
        if (isset($after)) {
            $view->setFusionPath($after);
            $view->render();
        }
    }

    /**
     * so that ${ self.assertTrue(true) works in eel }
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
