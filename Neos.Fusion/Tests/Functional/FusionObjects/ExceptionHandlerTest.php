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
 * Testcase for the Fusion exception handling
 *
 */
class ExceptionHandlerTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function exceptionalEelExpressionInPropertyIsHandledCorrectly()
    {
        $view = $this->buildView();
        $view->setFusionPath('exceptionHandler/eelExpressionInProperty');
        self::assertStringStartsWith('StartException while rendering exceptionHandler', $view->render());
    }


    /**
     * @test
     */
    public function exceptionalEelExpressionInOverrideIsHandledCorrectly()
    {
        $view = $this->buildView();
        $view->setFusionPath('exceptionHandler/eelExpressionInOverride');
        $output = $view->render();
        self::assertStringStartsWith('StartException while rendering exceptionHandler', $output);
        self::assertStringContainsString('myItems', $output, 'The override path should be visible in the message Fusion path');
    }

    /**
     * We trigger rendering of a Fusion object with a nested Fusion object being "evaluated". If an exception happens there,
     * the configured exceptionHandler needs to be triggered as well, even though the object has been rendered with "evaluate()"
     * and not with "render()"
     *
     * @test
     */
    public function exceptionHandlerIsEvaluatedForNestedFusionObjects()
    {
        $view = $this->buildView();
        $view->setFusionPath('exceptionHandler/nestedHandlerIsEvaluated');
        $output = $view->render();
        self::assertNotNull($output);
        self::assertStringStartsWith('Exception while rendering', $output);
        self::assertStringContainsString('Just testing an exception', $output);
    }
}
