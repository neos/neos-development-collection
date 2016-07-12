<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the TypoScript View
 *
 */
class ContextOverrideTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function basicContextOverrides()
    {
        $view = $this->buildView();
        $view->assignMultiple(array('var1' => 'var1', 'var2' => 'var2'));
        $view->setTypoScriptPath('contextOverride/test');
        $this->assertEquals('Xvar1var2Xvar1var2Xvar1var2XfooofooofoooboooboooboooXvar2Xvar2Y', $view->render());
    }
}
