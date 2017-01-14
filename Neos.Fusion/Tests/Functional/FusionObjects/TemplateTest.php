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
 * Testcase for the Fusion Template Object
 *
 */
class TemplateTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function basicFluidTemplateCanBeUsedForRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/basicTemplate');
        $this->assertEquals('Test Templatefoo', $view->render());
    }

    /**
     * @test
     */
    public function basicFluidTemplateContainsEelVariables()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/basicTemplateWithEelVariable');
        $this->assertEquals('Test Templatefoobar', $view->render());
    }

    /**
     * @test
     */
    public function customPartialPathCanBeSetOnRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/partial');
        $this->assertEquals('Test Template--partial contents', $view->render());
    }

    /**
     * @test
     */
    public function customLayoutPathCanBeSetOnRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/layout');
        $this->assertEquals('layout start -- Test Template -- layout end', $view->render());
    }

    /**
     * @test
     */
    public function typoScriptExceptionInObjectAccessIsHandledCorrectly()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/offsetAccessException');
        $this->assertStringStartsWith('Test TemplateException while rendering template', $view->render());
    }

    /**
     * @test
     */
    public function expressionCanBeOverridenWithSimpleValueForTemplate()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/overrideWithSimpleValueInTemplate');
        $this->assertSame('3', $view->render(), 'JSON encoded value should be a number');
    }
}
