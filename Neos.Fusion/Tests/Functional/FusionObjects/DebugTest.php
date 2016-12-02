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
 * Testcase for the Debug object
 *
 */
class DebugTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function debugEmptyValue()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('debug/empty');
        $lines = explode(chr(10), $view->render());
        $this->assertEquals($lines[1], 'NULL');
    }

    /**
     * @test
     */
    public function debugNullValue()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('debug/null');
        $lines = explode(chr(10), $view->render());
        $this->assertEquals($lines[1], 'NULL');
    }

    /**
     * @test
     */
    public function debugNullValueWithTitle()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('debug/nullWithTitle');
        $lines = explode(chr(10), $view->render());
        $this->assertEquals('Title', $lines[0]);
        $this->assertEquals('NULL', $lines[1]);
    }

    /**
     * @test
     */
    public function debugEelExpression()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('debug/eelExpression');
        $lines = explode(chr(10), $view->render());
        $this->assertEquals('string "hello world" (11)', $lines[1]);
    }

    /**
     * @test
     */
    public function debugTsObjectExpression()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('debug/tsObjectExpression');
        $lines = explode(chr(10), $view->render());
        $this->assertEquals('string "hello world" (11)', $lines[1]);
    }

    /**
     * @test
     */
    public function debugMultipleValues()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('debug/multipleValues');
        $lines = explode(chr(10), $view->render());
        $this->assertEquals('array(2)', $lines[1]);
        $this->assertEquals(' string "foo" (3) => string "foo" (3)', $lines[2]);
        $this->assertEquals(' string "bar" (3) => string "bar" (3)', $lines[3]);
    }
}
