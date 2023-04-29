<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the DebugConsole object
 *
 */
class DebugConsoleTest extends AbstractFusionObjectTest
{

    /**
     * @test
     */
    public function debugEmptyValue()
    {
        $view = $this->buildView();
        $view->setFusionPath('debugConsole/empty');
        $result = $view->render();
        self::assertEquals('<script>console.log("")</script>', $result);
    }

    /**
     * @test
     */
    public function debugNull()
    {
        $view = $this->buildView();
        $view->setFusionPath('debugConsole/null');
        $result = $view->render();
        self::assertEquals('<script>console.log("")</script>', $result);
    }

    /**
     * @test
     */
    public function debugNullWithTitle()
    {
        $view = $this->buildView();
        $view->setFusionPath('debugConsole/nullWithTitle');
        $result = $view->render();
        self::assertEquals('<script>console.log("", "Title")</script>', $result);
    }

    /**
     * @test
     */
    public function debugObject()
    {
        $view = $this->buildView();
        $view->setFusionPath('debugConsole/object');
        $result = $view->render();
        self::assertEquals('<script>console.log({"foo":"bar"})</script>', $result);
    }

    /**
     * @test
     */
    public function debugMultipleValues()
    {
        $view = $this->buildView();
        $view->setFusionPath('debugConsole/multipleValues');
        $result = $view->render();
        self::assertEquals('<script>console.log("a", "b", "c")</script>', $result);
    }
}
