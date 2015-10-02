<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
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
