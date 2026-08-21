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
use PHPUnit\Framework\Attributes\Test;
use Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException;

/**
 * Testcase for basic Fusion rendering
 *
 */
class BasicRenderingTest extends AbstractFusionObjectTestCase
{
    #[Test]
    public function basicRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('basicRendering/test');
        self::assertEquals('XHello World', $view->render());
    }

    #[Test]
    public function basicRenderingReusingFusionVariables()
    {
        $view = $this->buildView();
        $view->setFusionPath('basicRendering/reuseFusionVariables');
        self::assertEquals('XHello World', $view->render());
    }

    /**
     * The view cannot be rendered since it is broken
     * in this case an exception handler is called.
     * It takes the exceptions and shall produce some log message.
     *
     * The default handler for the tests rethrows the exception
     * TODO: test different exception handlers
     */
    #[Test]
    public function basicRenderingCrashing()
    {
        $this->expectException(InvalidTemplateResourceException::class);
        $view = $this->buildView();
        $view->setFusionPath('basicRendering/crashing');
        self::assertEquals('XHello World', $view->render());
    }

    #[Test]
    public function basicRenderingReusingFusionVariablesWithEel()
    {
        $view = $this->buildView();
        $view->setFusionPath('basicRendering/reuseFusionVariablesWithEel');
        self::assertEquals('XHello World', $view->render());
    }

    #[Test]
    public function complexExample()
    {
        $view = $this->buildView();
        $view->setFusionPath('basicRendering/complexExample/toRender');
        self::assertEquals('Static string post', $view->render());
    }

    #[Test]
    public function complexExample2()
    {
        $view = $this->buildView();
        $view->setFusionPath('basicRendering/complexExample2/toRender');
        self::assertEquals('Static string post', $view->render());
    }

    #[Test]
    public function plainValueCanBeOverridden()
    {
        $this->assertMultipleFusionPaths('overridden', 'basicRendering/overridePlainValueWith');
    }

    #[Test]
    public function eelExpressionCanBeOverridden()
    {
        $this->assertMultipleFusionPaths('overridden', 'basicRendering/overrideEelWith');
    }

    #[Test]
    public function fusionCanBeOverridden()
    {
        $this->assertMultipleFusionPaths('overridden', 'basicRendering/overrideFusionWith');
    }

    #[Test]
    public function contentIsNotTrimmed()
    {
        $view = $this->buildView();
        $view->setFusionPath('basicRendering/contentIsNotTrimmed');
        self::assertEquals('X I want to have some space after me ', $view->render());
    }
}
