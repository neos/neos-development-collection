<?php
namespace Neos\Fusion\Tests\Functional\TypoScriptObjects;

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
 * Testcase for basic TypoScript rendering
 *
 */
class NestedOverwritesAndProcessorsTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function overwritingSimpleValueWithProcessorWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('nestedOverwritesAndProcessors/deepProcessorAppliesToSimpleValue');
        $this->assertEquals('<div class="Xclass processed" tea="green"></div>', $view->render());
    }

    /**
     * @test
     */
    public function applyingProcessorToExpressionWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('nestedOverwritesAndProcessors/deepProcessorAppliesToEel');
        $this->assertEquals('<div class="Xclass" tea="green infused"></div>', $view->render());
    }

    /**
     * @test
     */
    public function applyingProcessorToNonExistingValueWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('nestedOverwritesAndProcessors/deepProcessorAppliesWithNoBaseValue');
        $this->assertEquals('<div class="Xclass" tea="green" coffee="harvey"></div>', $view->render());
    }
}
