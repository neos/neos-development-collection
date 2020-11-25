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
 * Testcase for the Case Fusion object
 *
 */
class CaseTest extends AbstractFusionObjectTest
{
    public function assertMatchingWorks($path)
    {
        $view = $this->buildView();
        $view->assign('cond', true);
        $view->setFusionPath($path);
        self::assertEquals('Xtestconditiontrue', $view->render());

        $view->assign('cond', false);
        self::assertEquals('Xtestconditionfalse', $view->render());
    }

    /**
     * @test
     */
    public function numericMatchingWorks()
    {
        $this->assertMatchingWorks('case/numericMatching');
    }

    /**
     * @test
     */
    public function matchingWithDebugModeWorks()
    {
        $view = $this->buildView();

        $view->setOption('debugMode', true);

        $view->assign('cond', true);
        $view->setFusionPath('case/numericMatching');
        self::assertStringContainsString('Xtestconditiontrue', $view->render());

        $view->assign('cond', false);
        self::assertStringContainsString('Xtestconditionfalse', $view->render());
    }

    /**
     * @test
     */
    public function positionalMatchingWorks()
    {
        $this->assertMatchingWorks('case/positionalMatching');
    }

    /**
     * @test
     */
    public function renderPathWillRenderAbsolutePath()
    {
        $this->assertMatchingWorks('case/renderPath');
    }

    /**
     * @test
     */
    public function renderPathWillWinOverType()
    {
        $this->assertMatchingWorks('case/renderPathWillWin');
    }

    /**
     * @test
     */
    public function ignorePropertiesWorks()
    {
        $this->assertMatchingWorks('case/ignoredPropertiesAreIgnored');
    }

    /**
     * @test
     */
    public function usingRendererWorks()
    {
        $this->assertMatchingWorks('case/renderer');
    }

    /**
     * @test
     */
    public function rendererWinsOverType()
    {
        $this->assertMatchingWorks('case/rendererWithType');
    }

    /**
     * @test
     */
    public function rendererWinsOverRenderPath()
    {
        $this->assertMatchingWorks('case/rendererWithRenderPath');
    }

    /**
     * @test
     */
    public function rendererWorksWithEelAndSimpleTypes()
    {
        $this->assertMatchingWorks('case/rendererWorksWithEelAndSimpleTypes');
    }

    /**
     * @test
     */
    public function rendererHasAccessToThis()
    {
        $view = $this->buildView();

        $view->setFusionPath('case/rendererHasAccessToThis');
        self::assertStringContainsString('foo', $view->render());
    }
}
