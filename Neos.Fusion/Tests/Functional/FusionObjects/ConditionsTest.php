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
        return array(
            array('conditions/simpleValueTrue', 'Foo'),
            array('conditions/simpleValueFalse', null),
            array('conditions/simpleValueAtLeastOneFalse', null),
            array('conditions/expressionTrue', 'Foo'),
            array('conditions/expressionFalse', null),
            array('conditions/expressionAtLeastOneFalse', null),
            array('conditions/objectTrue', 'Foo'),
            array('conditions/objectFalse', null),
            array('conditions/objectAtLeastOneFalse', null),
            array('conditions/objectThis', null),
            array('conditions/rawArray', array('key' => 'foo', 'nullValue' => null)),
            array('conditions/attributes', ' key="foo"'),
            array('conditions/supportForConditionInProcess', 'wrappedValue'),
            array('conditions/supportForConditionInProcessFalse', 'originalValue'),
            array('conditions/supportForConditionInProcessWithAdvancedProcess', 'wrappedValue'),
            array('conditions/supportForConditionInProcessWithAdvancedProcessFalse', 'originalValue'),
            array('conditions/processorOnSimpleValueWithCondition', null),
            array('conditions/processorOnExpressionWithCondition', null),
            array('conditions/processorOnObjectWithCondition', null)
        );
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
        $this->assertSame($expected, $view->render());
    }

    public function valuesForCondition()
    {
        return array(
            array(false, null),
            array(true, 'Rendered'),
            array(null, null),
            array(1, 'Rendered'),
            array('', null),
            array('0', null),
            array('Foo', 'Rendered'),
            array(0, null),
            array(-1, 'Rendered'),
            array([], null),
            array([12], 'Rendered')
        );
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
        $this->assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function conditionsInFusionObjectsWithSubEvaluationUsedInProcessorRenderCorrectly()
    {
        $view = $this->buildView();
        $view->setFusionPath('conditions/supportForFusionObjectWithSubEvaluationUsedInProcessor');
        $this->assertEquals('basic appended', $view->render());
    }
}
