<?php

namespace Neos\Fusion\Testing\Service;

class GenerateTestCases
{
    protected const PATH_LOWERCASE_TEST_SUFFIX = 'test';
    protected const PATH_TEST_OBJECT_TYPE = 'Neos.Fusion.Testing:TestCase';

    protected const TEST_KEY = 'test';
    protected const FUSION_KEY = 'fusion';

    protected const BEFORE_RENDERER_HOOK_KEY = 'beforeRender';
    protected const AFTER_RENDERER_HOOK_KEY = 'afterRender';

    public static function generateFromFirstLevelFusionPaths(array $fusionAst): \Generator
    {
        foreach ($fusionAst as $testCasePath => $testCaseAst) {

            $testCasePathIsTestCaseObject = isset($testCaseAst['__objectType']) && $testCaseAst['__objectType'] === self::PATH_TEST_OBJECT_TYPE;

            $testCasePathEndsWithTest = is_string($testCasePath) && strtolower(substr($testCasePath, -4)) === self::PATH_LOWERCASE_TEST_SUFFIX;

            // only continue with first level paths that end with 'Test' or are of type 'Neos.Fusion.Testing:TestCase'
            if ($testCasePathIsTestCaseObject === false && $testCasePathEndsWithTest === false) {
                continue;
            }

            /*
             * rendererTest {
             *     @test.1 {}
             *     @fusion.1 {}
             * }
             */

            ['__meta' => [
                self::TEST_KEY => $testConfigurationTestScenario,
                self::FUSION_KEY => $testConfigurationFusionProvider
            ]] = $testCaseAst;

            $providerFusionPaths = [];
            foreach ($testConfigurationFusionProvider as $providerName => $_) {
                $providerFusionPaths[$providerName] = "$testCasePath/__meta/" . self::FUSION_KEY . "/$providerName";
            }

            foreach ($testConfigurationTestScenario as $testScenario => ['__meta' => $scenarioConfiguration]) {

                $scenarioConfigurationFusionPath = "$testCasePath/__meta/" . self::TEST_KEY . "/$testScenario/__meta";

                $beforeHookFusionPath = isset($scenarioConfiguration[self::BEFORE_RENDERER_HOOK_KEY])
                    ? $scenarioConfigurationFusionPath . '/' . self::BEFORE_RENDERER_HOOK_KEY
                    : null;

                $afterHookFusionPath = isset($scenarioConfiguration[self::AFTER_RENDERER_HOOK_KEY])
                    ? $scenarioConfigurationFusionPath . '/' . self::AFTER_RENDERER_HOOK_KEY
                    : null;

                foreach ($providerFusionPaths as $providerName => $fusionPath) {
                    yield "$testCasePath with: " . self::TEST_KEY . "[$testScenario] " . self::FUSION_KEY . "[$providerName]" => [
                        $fusionPath,
                        $beforeHookFusionPath,
                        $afterHookFusionPath
                    ];
                }
            }
        }
    }
}
