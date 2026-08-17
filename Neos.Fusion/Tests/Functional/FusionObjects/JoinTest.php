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
 * Testcase for the Fusion Concat
 */
class JoinTest extends AbstractFusionObjectTestCase
{
    #[Test]
    public function basicOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('join/basicOrdering');
        self::assertEquals('Xtest10Xtest100', $view->render());
    }

    #[Test]
    public function positionalOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('join/positionalOrdering');
        self::assertEquals('XbeforeXmiddleXafter', $view->render());
    }

    #[Test]
    public function startEndOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('join/startEndOrdering');
        self::assertEquals('XbeforeXmiddleXafter', $view->render());
    }

    #[Test]
    public function advancedStartEndOrderingWorks()
    {
        $view = $this->buildView();

        $view->setFusionPath('join/advancedStartEndOrdering');
        self::assertEquals('XeXdXfoobarXfXgX100XbXaXc', $view->render());
    }

    #[Test]
    public function ignoredPropertiesWork()
    {
        $view = $this->buildView();

        $view->setFusionPath('join/ignoreProperties');
        self::assertEquals('XbeforeXafter', $view->render());
    }

    #[Test]
    public function glueWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('join/withGlue');
        self::assertEquals('a, b, d', $view->render());
    }
}
