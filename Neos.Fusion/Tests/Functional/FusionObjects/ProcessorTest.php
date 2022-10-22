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
 * Testcase for the Fusion View
 *
 */
class ProcessorTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function basicProcessorsWork()
    {
        $this->assertMultipleFusionPaths('Hello World foo', 'processors/newSyntax/basicProcessor/valueWithNested');
    }

    /**
     * @test
     */
    public function basicProcessorsBeforeValueWork()
    {
        $this->assertMultipleFusionPaths('Hello World foo', 'processors/newSyntax/processorBeforeValue/valueWithNested');
    }

    /**
     * @test
     */
    public function extendedSyntaxProcessorsWork()
    {
        $this->assertMultipleFusionPaths('Hello World foo', 'processors/newSyntax/extendedSyntaxProcessor/valueWithNested');
    }

    /**
     * https://github.com/neos/neos-development-collection/pull/3847
     * @test
     */
    public function plainValueOverriddenByPlainValueWorks()
    {
        $this->assertFusionPath('foo', 'processors/newSyntax/basicProcessor/plainValueOverriddenByPlainValue');
    }

    /**
     * Data Provider for processorsCanBeUnset
     *
     * @return array
     */
    public function dataProviderForUnsettingProcessors()
    {
        return [
            ['processors/newSyntax/unset/simple'],
            ['processors/newSyntax/unset/prototypes1'],
            ['processors/newSyntax/unset/prototypes2'],
            ['processors/newSyntax/unset/nestedScope/prototypes3']
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForUnsettingProcessors
     */
    public function processorsCanBeUnset($path)
    {
        $view = $this->buildView();
        $view->setFusionPath($path);
        self::assertEquals('Foobaz', $view->render());
    }

    /**
     * @test
     */
    public function usingThisInProcessorWorks()
    {
        $this->assertFusionPath('my value append', 'processors/newSyntax/usingThisInProcessor');
    }

    /**
     * @test
     */
    public function skippedLazyPropsInProcessor()
    {
        $view = $this->buildView();
        $view->setFusionPath('processors/newSyntax/skippedLazyPropsInProcessor');
        self::assertSame(['buz' => 'bar', 'lazyPropValue' => 'foo'], $view->render());
    }
}
