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
        self::assertEquals('original eel expression', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithSingleSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithSingleSpread');
        self::assertEquals('altered eel expression', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithInvalidFusionObjectSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithInvalidFusionObjectSpread');
        self::assertEquals('original eel expression', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithInvalidExpressionSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithInvalidExpressionSpread');
        self::assertEquals('original eel expression', $view->render());
    }

    /**
     * @test
     */
    public function eelValueInvalidCyclicExpressionSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueInvalidCyclicExpressionSpread');
        self::assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithFusionObjectSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithFusionObjectSpread');
        self::assertEquals('altered eel expression', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithMultipleSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithMultipleSpreads');
        self::assertEquals('altered eel expression 3', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithMultipleOrderedSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithMultipleOrderedSpreads');
        self::assertEquals('altered eel expression to be evaluated last', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithProcessorRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithProcessor');
        self::assertEquals('foo:original eel expression:bar', $view->render());
    }

    /**
     * @test
     */
    public function eelValueWithProcessorAndSingleSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithProcessorAndSingleSpread');
        self::assertEquals('foo:altered eel expression:bar', $view->render());
    }

    /**
     * @test
     */
    public function valueWithNonMatchingIfConditionRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfCondition');
        self::assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function valueWithNonMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfConditionThatUseSpreadValues');
        self::assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function valueWithNonMatchingIfConditionIfSpreadAltersValueRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfConditionIfSpreadAltersValue');
        self::assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function valueWithNonMatchingIfConditionIfSpreadAltersValueAndEnabledConditionRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfConditionIfSpreadAltersValueAndEnabledCondition');
        self::assertEquals('altered value', $view->render());
    }

    /**
     * @test
     */
    public function valueWithMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithMatchingIfConditionThatUseSpreadValues');
        self::assertEquals('enabled value', $view->render());
    }

    /**
     * @test
     */
    public function prototypeWithNonMatchingIfConditionRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderPrototypeWithNonMatchingIfCondition');
        self::assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function prototypeWithNonMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderPrototypeWithNonMatchingIfConditionThatUseSpreadValues');
        self::assertEquals(null, $view->render());
    }

    /**
     * @test
     */
    public function prototypeWithMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderPrototypeWithMatchingIfConditionThatUseSpreadValues');
        self::assertEquals('enabled value', $view->render());
    }

    /**
     * @test
     */
    public function nestedPrototypeRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderNestedPrototype');
        self::assertEquals('expression from nested prototypes', $view->render());
    }

    /**
     * @test
     */
    public function nestedPrototypeOverriddenWithSpreadsRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderNestedPrototypeOverriddenWithSpreads');
        self::assertEquals('i can change this', $view->render());
    }

    /**
     * @test
     */
    public function loopWithoutSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderLoopWithoutSpread');
        self::assertEquals('X1X2X2X3', $view->render());
    }

    /**
     * @test
     */
    public function loopWithSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderLoopWithSpread');
        self::assertEquals('X1X2X2X3', $view->render());
    }

    /**
     * @test
     */
    public function rendererWithTypeAndElementSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderRendererWithTypeAndElementSpread');
        self::assertEquals('XValueAppliedViaElementSpread', $view->render());
    }

    /**
     * @test
     */
    public function dataStructureWithSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderDataStructureWithSpread');
        self::assertEquals(
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
    public function joinWithPositionAndSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderJoinWithPositionAndSpread');
        self::assertEquals(
            'startmiddleModifiedendModified',
            $view->render()
        );
    }

    /**
     * @test
     */
    public function rendererWithNestedPropsInApply()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderWithNestedProps');
        self::assertEquals('::example::', $view->render());
    }
}
