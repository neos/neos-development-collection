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
 * Testcase for the TypoScript Array
 *
 */
class TypoScriptArrayTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function basicOrderingWorks()
    {
        $view = $this->buildView();

        $view->setTypoScriptPath('array/basicOrdering');
        $this->assertEquals('Xtest10Xtest100', $view->render());
    }

    /**
     * @test
     */
    public function positionalOrderingWorks()
    {
        $view = $this->buildView();

        $view->setTypoScriptPath('array/positionalOrdering');
        $this->assertEquals('XbeforeXmiddleXafter', $view->render());
    }

    /**
     * @test
     */
    public function startEndOrderingWorks()
    {
        $view = $this->buildView();

        $view->setTypoScriptPath('array/startEndOrdering');
        $this->assertEquals('XbeforeXmiddleXafter', $view->render());
    }

    /**
     * @test
     */
    public function advancedStartEndOrderingWorks()
    {
        $view = $this->buildView();

        $view->setTypoScriptPath('array/advancedStartEndOrdering');
        $this->assertEquals('XeXdXfoobarXfXgX100XbXaXc', $view->render());
    }

    /**
     * @test
     */
    public function ignoredPropertiesWork()
    {
        $view = $this->buildView();

        $view->setTypoScriptPath('array/ignoreProperties');
        $this->assertEquals('XbeforeXafter', $view->render());
    }
}
