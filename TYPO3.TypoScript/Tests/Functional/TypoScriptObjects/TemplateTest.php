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
 * Testcase for the TypoScript Template Object
 *
 */
class TemplateTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function basicFluidTemplateCanBeUsedForRendering()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('template/basicTemplate');
        $this->assertEquals('Test Templatefoo', $view->render());
    }

    /**
     * @test
     */
    public function basicFluidTemplateContainsEelVariables()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('template/basicTemplateWithEelVariable');
        $this->assertEquals('Test Templatefoobar', $view->render());
    }

    /**
     * @test
     */
    public function customPartialPathCanBeSetOnRendering()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('template/partial');
        $this->assertEquals('Test Template--partial contents', $view->render());
    }

    /**
     * @test
     */
    public function customLayoutPathCanBeSetOnRendering()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('template/layout');
        $this->assertEquals('layout start -- Test Template -- layout end', $view->render());
    }

    /**
     * @test
     */
    public function typoScriptExceptionInObjectAccessIsHandledCorrectly()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('template/offsetAccessException');
        $this->assertStringStartsWith('Test TemplateException while rendering template', $view->render());
    }
}
