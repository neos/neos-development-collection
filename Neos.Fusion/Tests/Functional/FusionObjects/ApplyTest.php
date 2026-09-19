<?php

namespace Neos\Fusion\Tests\Functional\FusionObjects;

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
 * Testcase for basic Fusion spread rendering
 *
 */
class ApplyTest extends AbstractFusionObjectTestCase
{
    #[Test]
    public function eelValueRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValue');
        self::assertEquals('original eel expression', $view->render());
    }

    #[Test]
    public function eelValueWithSingleSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithSingleSpread');
        self::assertEquals('altered eel expression', $view->render());
    }

    #[Test]
    public function eelValueWithInvalidFusionObjectSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithInvalidFusionObjectSpread');
        self::assertEquals('original eel expression', $view->render());
    }

    #[Test]
    public function eelValueWithInvalidExpressionSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithInvalidExpressionSpread');
        self::assertEquals('original eel expression', $view->render());
    }

    #[Test]
    public function eelValueInvalidCyclicExpressionSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueInvalidCyclicExpressionSpread');
        self::assertEquals(null, $view->render());
    }

    #[Test]
    public function eelValueWithFusionObjectSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithFusionObjectSpread');
        self::assertEquals('altered eel expression', $view->render());
    }

    #[Test]
    public function eelValueWithMultipleSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithMultipleSpreads');
        self::assertEquals('altered eel expression 3', $view->render());
    }

    #[Test]
    public function eelValueWithMultipleOrderedSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithMultipleOrderedSpreads');
        self::assertEquals('altered eel expression to be evaluated last', $view->render());
    }

    #[Test]
    public function eelValueWithProcessorRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithProcessor');
        self::assertEquals('foo:original eel expression:bar', $view->render());
    }

    #[Test]
    public function eelValueWithProcessorAndSingleSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderEelValueWithProcessorAndSingleSpread');
        self::assertEquals('foo:altered eel expression:bar', $view->render());
    }

    #[Test]
    public function valueWithNonMatchingIfConditionRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfCondition');
        self::assertEquals(null, $view->render());
    }

    #[Test]
    public function valueWithNonMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfConditionThatUseSpreadValues');
        self::assertEquals(null, $view->render());
    }

    #[Test]
    public function valueWithNonMatchingIfConditionIfSpreadAltersValueRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfConditionIfSpreadAltersValue');
        self::assertEquals(null, $view->render());
    }

    #[Test]
    public function valueWithNonMatchingIfConditionIfSpreadAltersValueAndEnabledConditionRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithNonMatchingIfConditionIfSpreadAltersValueAndEnabledCondition');
        self::assertEquals('altered value', $view->render());
    }

    #[Test]
    public function valueWithMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderValueWithMatchingIfConditionThatUseSpreadValues');
        self::assertEquals('enabled value', $view->render());
    }

    #[Test]
    public function prototypeWithNonMatchingIfConditionRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderPrototypeWithNonMatchingIfCondition');
        self::assertEquals(null, $view->render());
    }

    #[Test]
    public function prototypeWithNonMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderPrototypeWithNonMatchingIfConditionThatUseSpreadValues');
        self::assertEquals(null, $view->render());
    }

    #[Test]
    public function prototypeWithMatchingIfConditionThatUseSpreadValuesRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderPrototypeWithMatchingIfConditionThatUseSpreadValues');
        self::assertEquals('enabled value', $view->render());
    }

    #[Test]
    public function nestedPrototypeRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderNestedPrototype');
        self::assertEquals('expression from nested prototypes', $view->render());
    }

    #[Test]
    public function nestedPrototypeOverriddenWithSpreadsRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderNestedPrototypeOverriddenWithSpreads');
        self::assertEquals('i can change this', $view->render());
    }

    #[Test]
    public function loopWithoutSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderLoopWithoutSpread');
        self::assertEquals('X1X2X2X3', $view->render());
    }

    #[Test]
    public function loopWithSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderLoopWithSpread');
        self::assertEquals('X1X2X2X3', $view->render());
    }

    #[Test]
    public function rendererWithTypeAndElementSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderRendererWithTypeAndElementSpread');
        self::assertEquals('XValueAppliedViaElementSpread', $view->render());
    }

    #[Test]
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

    #[Test]
    public function joinWithPositionAndSpreadRendering()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderJoinWithPositionAndSpread');
        self::assertEquals(
            'startmiddleModifiedendModified',
            $view->render()
        );
    }

    #[Test]
    public function rendererWithNestedPropsInApply()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/renderWithNestedProps');
        self::assertEquals('::example::', $view->render());
    }

    #[Test]
    public function evaluateLazyPropsWithLastOneSkipped()
    {
        $view = $this->buildView();
        $view->setFusionPath('apply/evaluateLazyPropsWithLastOneSkipped');
        self::assertSame(['lazyPropValue' => 'foo'], $view->render());
    }
}
