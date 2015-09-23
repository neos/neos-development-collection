<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for conditional rendering (if)
 */
class ConditionsTest extends AbstractTypoScriptObjectTest
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
            array('conditions/attributes', ' key="foo"')
        );
    }

    /**
     * @test
     * @dataProvider conditionExamples
     */
    public function conditionsWork($path, $expected)
    {
        $view = $this->buildView();
        $view->setTypoScriptPath($path);
        $view->assign('foo', 'Foo');
        $this->assertSame($expected, $view->render());
    }

    public function valuesForCondition()
    {
        return array(
            array(false, null),
            array(true, 'Rendered'),
            array(null, 'Rendered'),
            array(1, 'Rendered'),
            array('0', 'Rendered'),
            array(0, 'Rendered')
        );
    }

    /**
     * @test
     * @dataProvider valuesForCondition
     */
    public function everythingButFalseIsEvaluated($conditionValue, $expected)
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('conditions/variableCondition');
        $view->assign('condition', $conditionValue);
        $this->assertSame($expected, $view->render());
    }
}
