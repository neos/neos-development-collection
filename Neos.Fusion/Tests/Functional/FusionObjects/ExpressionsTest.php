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

use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\RuntimeFactory;

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

    /**
     * The view and runtime of the AbstractFusionObjectTest
     * is not used to make sure the runtime context is empty.
     *
     * @test
     */
    public function usingEelWorksWithoutSetCurrentContextInRuntime()
    {
        $fusionAst = (new Parser())->parseFromSource(FusionSourceCodeCollection::fromString('root = ${"foo"}'))->toArray();

        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $runtime = (new RuntimeFactory())->create($fusionAst, $controllerContext);

        $renderedFusion = $runtime->render('root');

        self::assertSame('foo', $renderedFusion);
    }
}
