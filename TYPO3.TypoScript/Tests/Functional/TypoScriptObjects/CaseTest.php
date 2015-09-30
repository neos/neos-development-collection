<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Case TS object
 *
 */
class CaseTest extends AbstractTypoScriptObjectTest
{
    public function assertMatchingWorks($path)
    {
        $view = $this->buildView();
        $view->assign('cond', true);
        $view->setTypoScriptPath($path);
        $this->assertEquals('Xtestconditiontrue', $view->render());

        $view->assign('cond', false);
        $this->assertEquals('Xtestconditionfalse', $view->render());
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
        $view->setTypoScriptPath('case/numericMatching');
        $this->assertContains('Xtestconditiontrue', $view->render());

        $view->assign('cond', false);
        $this->assertContains('Xtestconditionfalse', $view->render());
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

        $view->setTypoScriptPath('case/rendererHasAccessToThis');
        $this->assertContains('foo', $view->render());
    }
}
