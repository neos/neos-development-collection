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
 * Testcase for the Iteration Fusion object
 *
 */
class LoopTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function basicLoopWorks()
    {
        $view = $this->buildView();
        $view->assign('items', ['element1', 'element2']);
        $view->setFusionPath('loop/basicLoop');
        self::assertEquals('Xelement1Xelement2', $view->render());
    }

    /**
     * @test
     */
    public function basicLoopWorksWithGlue()
    {
        $view = $this->buildView();
        $view->assign('items', ['element1', 'element2']);
        $view->setFusionPath('loop/basicLoopWithGlue');
        self::assertEquals('Xelement1, Xelement2', $view->render());
    }

    /**
     * @test
     */
    public function basicLoopWorksAndStillContainsOtherContextVariables()
    {
        $view = $this->buildView();
        $view->assign('items', ['element1', 'element2']);
        $view->assign('other', 'var');
        $view->setFusionPath('loop/basicLoopOtherContextVariables');
        self::assertEquals('Xelement1varXelement2var', $view->render());
    }

    /**
     * @test
     */
    public function emptyLoopReturnsEmptyString()
    {
        $view = $this->buildView();
        $view->assign('items', null);
        $view->setFusionPath('loop/basicLoop');
        self::assertEquals('', $view->render());
    }

    /**
     * @test
     */
    public function iterationInformationIsAddedToLoop()
    {
        $view = $this->buildView();
        $view->assign('items', ['element1', 'element2', 'element3', 'element4']);
        $view->setFusionPath('loop/iteration');
        self::assertEquals('Xelement1-0-1-1--1-Xelement2-1-2----1Xelement3-2-3---1-Xelement4-3-4--1--1', $view->render());
    }
}
