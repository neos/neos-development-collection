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
use Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException;

/**
 * Testcase for basic TypoScript rendering
 *
 */
class BasicRenderingTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function basicRendering()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('basicRendering/test');
        $this->assertEquals('XHello World', $view->render());
    }

    /**
     * @test
     */
    public function basicRenderingReusingTypoScriptVariables()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('basicRendering/reuseTypoScriptVariables');
        $this->assertEquals('XHello World', $view->render());
    }

    /**
     * The view cannot be rendered since it is broken
     * in this case an exception handler is called.
     * It takes the exceptions and shall produce some log message.
     *
     * The default handler for the tests rethrows the exception
     * TODO: test different exception handlers
     *
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function basicRenderingCrashing()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('basicRendering/crashing');
        $this->assertEquals('XHello World', $view->render());
    }

    /**
     * @test
     */
    public function basicRenderingReusingTypoScriptVariablesWithEel()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('basicRendering/reuseTypoScriptVariablesWithEel');
        $this->assertEquals('XHello World', $view->render());
    }

    /**
     * @test
     */
    public function complexExample()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('basicRendering/complexExample/toRender');
        $this->assertEquals('Static string post', $view->render());
    }

    /**
     * @test
     */
    public function complexExample2()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('basicRendering/complexExample2/toRender');
        $this->assertEquals('Static string post', $view->render());
    }

    /**
     * @test
     */
    public function plainValueCanBeOverridden()
    {
        $this->assertMultipleTypoScriptPaths('overridden', 'basicRendering/overridePlainValueWith');
    }

    /**
     * @test
     */
    public function eelExpressionCanBeOverridden()
    {
        $this->assertMultipleTypoScriptPaths('overridden', 'basicRendering/overrideEelWith');
    }

    /**
     * @test
     */
    public function typoScriptCanBeOverridden()
    {
        $this->assertMultipleTypoScriptPaths('overridden', 'basicRendering/overrideTypoScriptWith');
    }

    /**
     * @test
     */
    public function contentIsNotTrimmed()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('basicRendering/contentIsNotTrimmed');
        $this->assertEquals('X I want to have some space after me ', $view->render());
    }
}
