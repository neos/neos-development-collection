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
 * Testcase for the Component Fusion object
 *
 */
class ComponentTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function componentBasicRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/basicRenderer');
        self::assertEquals('Hello World', $view->render());
    }

    /**
     * @test
     */
    public function componentNestedRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/nestedRenderer');
        self::assertEquals('Hello World', $view->render());
    }

    /**
     * @test
     */
    public function componentStaticRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/staticRenderer');
        self::assertEquals('Hello World', $view->render());
    }

    /**
     * @test
     */
    public function componentSandboxRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/sandboxRenderer');
        self::assertEquals('Hello ', $view->render());
    }

    /**
     * @test
     */
    public function componentLazyRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/lazyRenderer');
        self::assertEquals('Hello', $view->render());
    }

    /**
     * @test
     */
    public function componentWrapperRenderer()
    {
        $view = $this->buildView();
        $view->setFusionPath('component/wrapperRenderer');
        self::assertEquals('Default content', $view->render());
    }
}
