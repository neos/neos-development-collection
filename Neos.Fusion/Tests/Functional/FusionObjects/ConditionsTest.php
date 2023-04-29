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
 * Testcase for conditional rendering (if)
 */
class ConditionsTest extends AbstractFusionObjectTest
{
    public function conditionExamples()
    {
        return [
            ['conditions/simpleValueTrue', 'Foo'],
            ['conditions/simpleValueFalse', null],
            ['conditions/simpleValueAtLeastOneFalse', null],
            ['conditions/expressionTrue', 'Foo'],
            ['conditions/expressionFalse', null],
            ['conditions/expressionAtLeastOneFalse', null],
            ['conditions/objectTrue', 'Foo'],
            ['conditions/objectFalse', null],
            ['conditions/objectAtLeastOneFalse', null],
            ['conditions/objectThis', null],
            ['conditions/dataStructure', ['key' => 'foo', 'nullValue' => null]],
            ['conditions/attributes', ' key="foo"'],
            ['conditions/supportForConditionInProcess', 'wrappedValue'],
            ['conditions/supportForConditionInProcessFalse', 'originalValue'],
            ['conditions/supportForConditionInProcessWithAdvancedProcess', 'wrappedValue'],
            ['conditions/supportForConditionInProcessWithAdvancedProcessFalse', 'originalValue'],
            ['conditions/processorOnSimpleValueWithCondition', null],
            ['conditions/processorOnExpressionWithCondition', null],
            ['conditions/processorOnObjectWithCondition', null]
        ];
    }

    /**
     * @test
     * @dataProvider conditionExamples
     */
    public function conditionsWork($path, $expected)
    {
        $view = $this->buildView();
        $view->setFusionPath($path);
        $view->assign('foo', 'Foo');
        self::assertSame($expected, $view->render());
    }

    public function valuesForCondition()
    {
        return [
            [false, null],
            [true, 'Rendered'],
            [null, null],
            [1, 'Rendered'],
            ['', null],
            ['0', null],
            ['Foo', 'Rendered'],
            [0, null],
            [-1, 'Rendered'],
            [[], null],
            [[12], 'Rendered']
        ];
    }

    /**
     * @test
     * @dataProvider valuesForCondition
     */
    public function everythingButFalseIsEvaluated($conditionValue, $expected)
    {
        $view = $this->buildView();
        $view->setFusionPath('conditions/variableCondition');
        $view->assign('condition', $conditionValue);
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function conditionsInFusionObjectsWithSubEvaluationUsedInProcessorRenderCorrectly()
    {
        $view = $this->buildView();
        $view->setFusionPath('conditions/supportForFusionObjectWithSubEvaluationUsedInProcessor');
        self::assertEquals('basic appended', $view->render());
    }
}
