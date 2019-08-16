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
        self::assertEquals('Test Templatefoo', $view->render());
    }

    /**
     * @test
     */
    public function basicFluidTemplateContainsEelVariables()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/basicTemplateWithEelVariable');
        self::assertEquals('Test Templatefoobar', $view->render());
    }

    /**
     * @test
     */
    public function customPartialPathCanBeSetOnRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/partial');
        self::assertEquals('Test Template--partial contents', $view->render());
    }

    /**
     * @test
     */
    public function customLayoutPathCanBeSetOnRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/layout');
        self::assertEquals('layout start -- Test Template -- layout end', $view->render());
    }

    /**
     * @test
     */
    public function fusionExceptionInObjectAccessIsHandledCorrectly()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/offsetAccessException');
        self::assertStringStartsWith('Test TemplateException while rendering template', $view->render());
    }

    /**
     * @test
     */
    public function expressionCanBeOverridenWithSimpleValueForTemplate()
    {
        $view = $this->buildView();
        $view->setFusionPath('template/overrideWithSimpleValueInTemplate');
        self::assertSame('3', $view->render(), 'JSON encoded value should be a number');
    }
}
