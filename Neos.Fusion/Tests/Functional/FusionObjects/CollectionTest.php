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
 * Testcase for the Collection Fusion object
 *
 */
class CollectionTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function basicCollectionWorks()
    {
        $view = $this->buildView();
        $view->assign('collection', ['element1', 'element2']);
        $view->setFusionPath('collection/basicLoop');
        self::assertEquals('Xelement1Xelement2', $view->render());
    }

    /**
     * @test
     */
    public function basicCollectionWorksAndStillContainsOtherContextVariables()
    {
        $view = $this->buildView();
        $view->assign('collection', ['element1', 'element2']);
        $view->assign('other', 'var');
        $view->setFusionPath('collection/basicLoopOtherContextVariables');
        self::assertEquals('Xelement1varXelement2var', $view->render());
    }

    /**
     * @test
     */
    public function emptyCollectionReturnsEmptyString()
    {
        $view = $this->buildView();
        $view->assign('collection', null);
        $view->setFusionPath('collection/basicLoop');
        self::assertEquals('', $view->render());
    }

    /**
     * @test
     */
    public function iterationInformationIsAddedToCollection()
    {
        $view = $this->buildView();
        $view->assign('collection', ['element1', 'element2', 'element3', 'element4']);
        $view->setFusionPath('collection/iteration');
        self::assertEquals('Xelement1-0-1-1--1-Xelement2-1-2----1Xelement3-2-3---1-Xelement4-3-4--1--1', $view->render());
    }
}
