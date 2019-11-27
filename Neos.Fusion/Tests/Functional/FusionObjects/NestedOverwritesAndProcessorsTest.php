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
 * Testcase for basic Fusion rendering
 *
 */
class NestedOverwritesAndProcessorsTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function overwritingSimpleValueWithProcessorWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('nestedOverwritesAndProcessors/deepProcessorAppliesToSimpleValue');
        self::assertEquals('<div class="Xclass processed" tea="green"></div>', $view->render());
    }

    /**
     * @test
     */
    public function applyingProcessorToExpressionWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('nestedOverwritesAndProcessors/deepProcessorAppliesToEel');
        self::assertEquals('<div class="Xclass" tea="green infused"></div>', $view->render());
    }

    /**
     * @test
     */
    public function applyingProcessorToNonExistingValueWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('nestedOverwritesAndProcessors/deepProcessorAppliesWithNoBaseValue');
        self::assertEquals('<div class="Xclass" tea="green" coffee="harvey"></div>', $view->render());
    }
}
