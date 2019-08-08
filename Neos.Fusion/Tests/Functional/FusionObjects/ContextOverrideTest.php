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
 * Testcase for the Fusion View
 *
 */
class ContextOverrideTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function basicContextOverrides()
    {
        $view = $this->buildView();
        $view->assignMultiple(['var1' => 'var1', 'var2' => 'var2']);
        $view->setFusionPath('contextOverride/test');
        self::assertEquals('Xvar1var2Xvar1var2Xvar1var2XfooofooofoooboooboooboooXvar2Xvar2Y', $view->render());
    }
}
