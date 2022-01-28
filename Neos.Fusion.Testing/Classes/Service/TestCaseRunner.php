<?php

namespace Neos\Fusion\Testing\Service;

use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Testing\View\AllowedContextFusionView;
use PHPUnit\Framework\TestCase;

class TestCaseRunner
{
    public static function assertFusionRendersAsExpected(TestCase $testCase, string $fixturesRootFusion, string $fusionRenderPath, ?string $beforeHookPath, ?string $afterHookPath)
    {
        $view = new AllowedContextFusionView();

        $controllerContext = $testCase->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();

        $view->setControllerContext($controllerContext);

        // todo do we need this?: Error: Call to a member function getControllerPackageKey() on null
        $view->setPackageKey('Neos.Fusion.Testing');

        // todo prevent reparsing Fusion.
        $view->setFusionPathPattern($fixturesRootFusion);

        $view->assign('self', $testCase);
        $view->assign('view', $view);

        // @beforeRender eel hook
        if (isset($beforeHookPath)) {
            $view->setFusionPath($beforeHookPath);
            $view->render();
        }

        $view->setFusionPath($fusionRenderPath);
        $result = $view->render();

        $view->assign('result', $result);

        // @afterRender eel hook
        if (isset($afterHookPath)) {
            $view->setFusionPath($afterHookPath);
            $view->render();
        }
    }
}
