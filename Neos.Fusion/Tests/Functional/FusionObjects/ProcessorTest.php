<?php

namespace Neos\Fusion\Tests\Functional\FusionObjects;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

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
 * Testcase for the Fusion View
 *
 */
class ProcessorTest extends AbstractFusionObjectTestCase
{
    #[Test]
    public function basicProcessorsWork()
    {
        $this->assertMultipleFusionPaths('Hello World foo', 'processors/newSyntax/basicProcessor/valueWithNested');
    }

    #[Test]
    public function basicProcessorsBeforeValueWork()
    {
        $this->assertMultipleFusionPaths('Hello World foo', 'processors/newSyntax/processorBeforeValue/valueWithNested');
    }

    #[Test]
    public function extendedSyntaxProcessorsWork()
    {
        $this->assertMultipleFusionPaths('Hello World foo', 'processors/newSyntax/extendedSyntaxProcessor/valueWithNested');
    }

    /**
     * https://github.com/neos/neos-development-collection/pull/3847
     */
    #[Test]
    public function plainValueOverriddenByPlainValueWorks()
    {
        $this->assertFusionPath('foo', 'processors/newSyntax/basicProcessor/plainValueOverriddenByPlainValue');
    }

    /**
     * Data Provider for processorsCanBeUnset
     *
     * @return array
     */
    public static function dataProviderForUnsettingProcessors()
    {
        return [
            ['processors/newSyntax/unset/simple'],
            ['processors/newSyntax/unset/prototypes1'],
            ['processors/newSyntax/unset/prototypes2'],
            ['processors/newSyntax/unset/nestedScope/prototypes3']
        ];
    }

    #[DataProvider('dataProviderForUnsettingProcessors')]
    #[Test]
    public function processorsCanBeUnset($path)
    {
        $view = $this->buildView();
        $view->setFusionPath($path);
        self::assertEquals('Foobaz', $view->render());
    }

    #[Test]
    public function usingThisInProcessorWorks()
    {
        $this->assertFusionPath('my value append', 'processors/newSyntax/usingThisInProcessor');
    }

    #[Test]
    public function skippedLazyPropsInProcessor()
    {
        $view = $this->buildView();
        $view->setFusionPath('processors/newSyntax/skippedLazyPropsInProcessor');
        self::assertSame(['buz' => 'bar', 'lazyPropValue' => 'foo'], $view->render());
    }
}
