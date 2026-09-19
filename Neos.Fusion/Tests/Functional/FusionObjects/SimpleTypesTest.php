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
use Neos\Fusion\Exception\MissingFusionImplementationException;
use Neos\Fusion\Exception\MissingFusionObjectException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Fusion View
 *
 */
class SimpleTypesTest extends AbstractFusionObjectTestCase
{
    #[Test]
    public function valuesCanBeExpressedAsSimpleValueAsEelAsTypoScropt()
    {
        $this->assertMultipleFusionPaths('A simple string value is not a Fusion object', 'simpleTypes/stringAs');
    }

    #[Test]
    public function fusionPropertiesCanContainSimpleValueOrEelOrTypoScropt()
    {
        $this->assertMultipleFusionPaths('A simple value', 'simpleTypes/valueWithNested');
    }

    #[Test]
    public function booleanSimpleTypeWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('simpleTypes/booleanFalse');
        self::assertSame(false, $view->render());
        $view->setFusionPath('simpleTypes/booleanTrue');
        self::assertTrue($view->render());
    }

    #[Test]
    public function nullSimpleTypeWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('simpleTypes/null');
        self::assertNull($view->render());
    }

    #[Test]
    public function processorOnSimpleTypeWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('simpleTypes/wrappedString');
        self::assertSame('Hello, Foo', $view->render());
    }

    #[Test]
    public function renderingObjectWithMissingImplementationThrowsException()
    {
        $this->expectException(MissingFusionImplementationException::class);
        $view = $this->buildView();
        $view->setFusionPath('simpleTypes/missingImplementation');
        $view->render();
    }

    #[Test]
    public function renderingNonExistingPathThrowsException()
    {
        $this->expectException(MissingFusionObjectException::class);
        $view = $this->buildView();
        $view->setFusionPath('simpleTypes/nonExistingValue');
        $view->render();
    }
}
