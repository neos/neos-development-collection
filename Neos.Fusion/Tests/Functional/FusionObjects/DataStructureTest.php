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
 * Testcase for the Fusion Dictionary
 */
class DataStructureTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function basicOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('dataStructure/basicOrdering');
        $this->assertEquals([10 => 'Xtest10', 100 => 'Xtest100'], $view->render());
    }

    /**
     * @test
     */
    public function positionalOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('dataStructure/positionalOrdering');
        $this->assertEquals(['c' => 'Xbefore', 'f' => 'Xmiddle', 'a' => 'Xafter'], $view->render());
    }

    /**
     * @test
     */
    public function startEndOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('dataStructure/startEndOrdering');
        $this->assertEquals(['c' => 'Xbefore', 'f' => 'Xmiddle', 'a' => 'Xafter'], $view->render());
    }

    /**
     * @test
     */
    public function advancedStartEndOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('dataStructure/advancedStartEndOrdering');
        $this->assertEquals(['e' => 'Xe', 'd' => 'Xd', 'foobar' => 'Xfoobar', 'f' => 'Xf', 'g' => 'Xg', 100 => 'X100', 'b' => 'Xb', 'a' => 'Xa', 'c' => 'Xc'], $view->render());
    }

    /**
     * @test
     */
    public function ignoredPropertiesWork()
    {
        $view = $this->buildView();

        $view->setFusionPath('dataStructure/ignoreProperties');
        $this->assertEquals(['c' => 'Xbefore', 'a' => 'Xafter'], $view->render());
    }
}
