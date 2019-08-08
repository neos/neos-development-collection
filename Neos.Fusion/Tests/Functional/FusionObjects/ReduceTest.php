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
 * Testcase for the Reduction Fusion object
 *
 */
class ReduceTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function basicReductionWorks()
    {
        $view = $this->buildView();
        $view->assign('items', ['element1', 'element2']);
        $view->assign('initialValue', 'InitialValue::');
        $view->setFusionPath('reduce/basicLoop');
        self::assertEquals('XXInitialValue::element1element2', $view->render());
    }

    /**
     * @test
     */
    public function basicReductionAddsNumbers()
    {
        $view = $this->buildView();
        $view->assign('items', [1,2,3,4]);
        $view->assign('initialValue', 5);
        $view->setFusionPath('reduce/additionLoop');
        self::assertEquals(15, $view->render());
    }

    /**
     * @test
     */
    public function basicReductionWorksAndStillContainsOtherContextVariables()
    {
        $view = $this->buildView();
        $view->assign('items', ['element1', 'element2']);
        $view->assign('other', 'var');
        $view->setFusionPath('reduce/basicLoopOtherContextVariables');
        self::assertEquals('XXelement1varelement2var', $view->render());
    }

    /**
     * @test
     */
    public function emptyReductionReturnsInitialValue()
    {
        $initialValue = '::InitialValue::';
        $view = $this->buildView();
        $view->assign('items', null);
        $view->assign('initialValue', $initialValue);
        $view->setFusionPath('reduce/basicLoop');
        self::assertEquals($initialValue, $view->render());
    }

    /**
     * @test
     */
    public function iterationInformationIsAddedToReduction()
    {
        $view = $this->buildView();
        $view->assign('items', ['element1', 'element2', 'element3', 'element4']);
        $view->setFusionPath('reduce/iteration');
        self::assertEquals('::element1-0-1-1--1-::element2-1-2----1::element3-2-3---1-::element4-3-4--1--1', $view->render());
    }
}
