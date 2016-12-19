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
 * Testcase for Eel expressions in TypoScript
 */
class ExpressionsTest extends AbstractFusionObjectTest
{
    public function expressionExamples()
    {
        return array(
            array('expressions/calculus', 42),
            array('expressions/stringHelper', 'BAR'),
            array('expressions/dateHelper', '14.07.2013 12:14'),
            array('expressions/arrayHelper', 3),
            array('expressions/customHelper', 'Flow'),
            array('expressions/flowQuery', 3)
        );
    }

    /**
     * @test
     * @dataProvider expressionExamples
     */
    public function expressionsWork($path, $expected)
    {
        $view = $this->buildView();
        $view->setFusionPath($path);
        $view->assign('foo', 'Bar');
        $this->assertSame($expected, $view->render());
    }
}
