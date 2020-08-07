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
 * Testcase for basic Fusion spread rendering
 *
 */
class ApplyTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function eelValueRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValue');
        $this->assertEquals('original eel expression', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithSingleSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithSingleSpread');
        $this->assertEquals('altered eel expression', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithInvalidFusionObjectSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithInvalidFusionObjectSpread');
        $this->assertEquals('original eel expression', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithInvalidExpressionSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithInvalidExpressionSpread');
        $this->assertEquals('original eel expression', $view->render());
    }

    /**
     * @test
     */
    public function eelValueInvalidCyclicExpressionSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueInvalidCyclicExpressionSpread');
        $this->assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithFusionObjectSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithFusionObjectSpread');
        $this->assertEquals('altered eel expression', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithMultipleSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithMultipleSpreads');
        $this->assertEquals('altered eel expression 3', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithMultipleOrderedSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithMultipleOrderedSpreads');
        $this->assertEquals('altered eel expression to be evaluated last', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithProcessorRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithProcessor');
        $this->assertEquals('foo:original eel expression:bar', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithProcessorAndSingleSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithProcessorAndSingleSpread');
        $this->assertEquals('foo:altered eel expression:bar', $view->render());
    }

    /**
     * @test
     */
    public function valueWithNonMatchingIfConditionRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfCondition');
        $this->assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function valueWithNonMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfConditionThatUseSpreadValues');
        $this->assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function valueWithNonMatchingIfConditionIfSpreadAltersValueRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfConditionIfSpreadAltersValue');
        $this->assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function valueWithNonMatchingIfConditionIfSpreadAltersValueAndEnabledConditionRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfConditionIfSpreadAltersValueAndEnabledCondition');
        $this->assertEquals('altered value', $view->render());
    }

    /**
     * @test
     */
    public function valueWithMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithMatchingIfConditionThatUseSpreadValues');
        $this->assertEquals('enabled value', $view->render());
    }

    /**
     * @test
     */
    public function prototypeWithNonMatchingIfConditionRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderPrototypeWithNonMatchingIfCondition');
        $this->assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function prototypeWithNonMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderPrototypeWithNonMatchingIfConditionThatUseSpreadValues');
        $this->assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function prototypeWithMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderPrototypeWithMatchingIfConditionThatUseSpreadValues');
        $this->assertEquals('enabled value', $view->render());
    }

    /**
     * @test
     */
    public function nestedPrototypeRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderNestedPrototype');
        $this->assertEquals('expression from nested prototypes', $view->render());
    }

    /**
     * @test
     */
    public function nestedPrototypeOverriddenWithSpreadsRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderNestedPrototypeOverriddenWithSpreads');
        $this->assertEquals('i can change this', $view->render());
    }

    /**
     * @test
     */
    public function collectionWithoutSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderCollectionWithoutSpread');
        $this->assertEquals('X1X2X2X3', $view->render());
    }

    /**
     * @test
     */
    public function collectionWithSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderCollectionWithSpread');
        $this->assertEquals('X1X2X2X3', $view->render());
    }

    /**
     * @test
     */
    public function rendererWithTypeAndElementSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderRendererWithTypeAndElementSpread');
        $this->assertEquals('XValueAppliedViaElementSpread', $view->render());
    }

    /**
     * @test
     */
    public function arrayWithSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderRawArrayWithSpread');
        $this->assertEquals(
            [
            'key' => 'original value',
            'alter' => 'altered value',
            'add' => 'added value'
        ],
            $view->render()
        );
    }

    /**
     * @test
     */
    public function arrayWithPositionAndSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderArrayWithPositionAndSpread');
        $this->assertEquals(
            'startmiddleModifiedendModified',
            $view->render()
        );
    }
}
