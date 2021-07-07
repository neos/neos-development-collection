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
 * Testcase for the Memo object
 *
 */
class MemoTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function returnsSameResult()
    {
        $view = $this->buildView();
        $view->setFusionPath('memo/returnsValue');
        $result = $view->render();
        self::assertEquals(2, $result);

        $view->setFusionPath('memo/returnsPreviousValueForDiscriminator');
        $secondResult = $view->render();

        self::assertEquals($result, $secondResult);
    }
}
