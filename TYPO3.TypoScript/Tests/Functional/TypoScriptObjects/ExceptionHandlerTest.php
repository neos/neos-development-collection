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
 * Testcase for the TypoScript exception handling
 *
 */
class ExceptionHandlerTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function exceptionalEelExpressionInPropertyIsHandledCorrectly()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('exceptionHandler/eelExpressionInProperty');
        $this->assertStringStartsWith('StartException while rendering exceptionHandler', $view->render());
    }


    /**
     * @test
     */
    public function exceptionalEelExpressionInOverrideIsHandledCorrectly()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('exceptionHandler/eelExpressionInOverride');
        $output = $view->render();
        $this->assertStringStartsWith('StartException while rendering exceptionHandler', $output);
        $this->assertContains('myCollection', $output, 'The override path should be visible in the message TypoScript path');
    }

    /**
     * We trigger rendering of a TypoScript object with a nested TS object being "evaluated". If an exception happens there,
     * the configured exceptionHandler needs to be triggered as well, even though the object has been rendered with "evaluate()"
     * and not with "render()"
     *
     * @test
     */
    public function exceptionHandlerIsEvaluatedForNestedTypoScriptObjects()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('exceptionHandler/nestedHandlerIsEvaluated');
        $output = $view->render();
        $this->assertNotNull($output);
        $this->assertStringStartsWith('Exception while rendering', $output);
        $this->assertContains('Just testing an exception', $output);
    }
}
