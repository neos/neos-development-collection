<?php

namespace Neos\Fusion\Tests\Functional\FusionObjects;

use PHPUnit\Framework\Attributes\Test;

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
class CaseTest extends AbstractFusionObjectTestCase
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

    #[Test]
    public function numericMatchingWorks()
    {
        $this->assertMatchingWorks('case/numericMatching');
    }

    #[Test]
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

    #[Test]
    public function positionalMatchingWorks()
    {
        $this->assertMatchingWorks('case/positionalMatching');
    }

    #[Test]
    public function renderPathWillRenderAbsolutePath()
    {
        $this->assertMatchingWorks('case/renderPath');
    }

    #[Test]
    public function renderPathWillWinOverType()
    {
        $this->assertMatchingWorks('case/renderPathWillWin');
    }

    #[Test]
    public function ignorePropertiesWorks()
    {
        $this->assertMatchingWorks('case/ignoredPropertiesAreIgnored');
    }

    #[Test]
    public function usingRendererWorks()
    {
        $this->assertMatchingWorks('case/renderer');
    }

    #[Test]
    public function rendererWinsOverType()
    {
        $this->assertMatchingWorks('case/rendererWithType');
    }

    #[Test]
    public function rendererWinsOverRenderPath()
    {
        $this->assertMatchingWorks('case/rendererWithRenderPath');
    }

    #[Test]
    public function rendererWorksWithEelAndSimpleTypes()
    {
        $this->assertMatchingWorks('case/rendererWorksWithEelAndSimpleTypes');
    }

    #[Test]
    public function rendererHasAccessToThis()
    {
        $view = $this->buildView();

        $view->setFusionPath('case/rendererHasAccessToThis');
        self::assertStringContainsString('foo', $view->render());
    }
}
