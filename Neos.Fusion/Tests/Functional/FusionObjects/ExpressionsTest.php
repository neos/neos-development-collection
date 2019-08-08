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
 * Testcase for Eel expressions in Fusion
 */
class ExpressionsTest extends AbstractFusionObjectTest
{
    public function expressionExamples()
    {
        return [
            ['expressions/calculus', 42],
            ['expressions/stringHelper', 'BAR'],
            ['expressions/dateHelper', '14.07.2013 12:14'],
            ['expressions/arrayHelper', 3],
            ['expressions/customHelper', 'Flow'],
            ['expressions/flowQuery', 3]
        ];
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
        self::assertSame($expected, $view->render());
    }
}
