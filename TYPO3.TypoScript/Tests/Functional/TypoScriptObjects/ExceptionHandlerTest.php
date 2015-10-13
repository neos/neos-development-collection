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
