<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript View
 *
 */
class SimpleTypesTest extends AbstractTypoScriptObjectTest
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
        $view->setTypoScriptPath('simpleTypes/booleanFalse');
        $this->assertSame(false, $view->render());
        $view->setTypoScriptPath('simpleTypes/booleanTrue');
        $this->assertTrue($view->render());
    }

    /**
     * @test
     */
    public function nullSimpleTypeWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('simpleTypes/null');
        $this->assertNull($view->render());
    }

    /**
     * @test
     */
    public function processorOnSimpleTypeWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('simpleTypes/wrappedString');
        $this->assertSame('Hello, Foo', $view->render());
    }

    /**
     * @test
     * @expectedException \TYPO3\TypoScript\Exception\MissingTypoScriptImplementationException
     */
    public function renderingObjectWithMissingImplementationThrowsException()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('simpleTypes/missingImplementation');
        $view->render();
    }

    /**
     * @test
     * @expectedException \TYPO3\TypoScript\Exception\MissingTypoScriptObjectException
     */
    public function renderingNonExistingPathThrowsException()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('simpleTypes/nonExistingValue');
        $view->render();
    }
}
