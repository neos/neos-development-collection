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
 * Testcase for the TypoScript View
 *
 */
class SimpleTypesTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function valuesCanBeExpressedAsSimpleValueAsEelAsTypoScropt()
    {
        $this->assertMultipleTypoScriptPaths('A simple string value is not a TypoScript object', 'simpleTypes/stringAs');
    }

    /**
     * @test
     */
    public function typoScriptPropertiesCanContainSimpleValueOrEelOrTypoScropt()
    {
        $this->assertMultipleTypoScriptPaths('A simple value', 'simpleTypes/valueWithNested');
    }

    /**
     * @test
     */
    public function booleanSimpleTypeWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('simpleTypes/booleanFalse');
        $this->assertSame(false, $view->render());
        $view->setFusionPath('simpleTypes/booleanTrue');
        $this->assertTrue($view->render());
    }

    /**
     * @test
     */
    public function nullSimpleTypeWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('simpleTypes/null');
        $this->assertNull($view->render());
    }

    /**
     * @test
     */
    public function processorOnSimpleTypeWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('simpleTypes/wrappedString');
        $this->assertSame('Hello, Foo', $view->render());
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Exception\MissingFusionImplementationException
     */
    public function renderingObjectWithMissingImplementationThrowsException()
    {
        $view = $this->buildView();
        $view->setFusionPath('simpleTypes/missingImplementation');
        $view->render();
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Exception\MissingFusionObjectException
     */
    public function renderingNonExistingPathThrowsException()
    {
        $view = $this->buildView();
        $view->setFusionPath('simpleTypes/nonExistingValue');
        $view->render();
    }
}
