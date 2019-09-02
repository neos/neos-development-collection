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

use Neos\Fusion\Service\DebugStack;

/**
 * Testcase for the Debug object
 *
 */
class DebugTest extends AbstractFusionObjectTest
{
    /**
     * @var DebugStack
     */
    protected $debugStack;

    public function setUp(): void
    {
        parent::setUp();
        $this->debugStack = $this->objectManager->get(DebugStack::class);
        $this->debugStack->flush();
    }

    /**
     * @test
     */
    public function debugEmptyValue()
    {
        $view = $this->buildView();
        $view->setFusionPath('debug/empty');
        $view->render();
        $result = $this->debugStack->dump();
        $lines = explode(chr(10), $result);
        self::assertEquals('NULL', $lines[1]);
    }

    /**
     * @test
     */
    public function debugNullValue()
    {
        $view = $this->buildView();
        $view->setFusionPath('debug/null');
        $view->render();
        $result = $this->debugStack->dump();
        $lines = explode(chr(10), $result);
        self::assertEquals('NULL', $lines[1]);
    }

    /**
     * @test
     */
    public function debugNullValueWithTitle()
    {
        $view = $this->buildView();
        $view->setFusionPath('debug/nullWithTitle');
        $view->render();
        $result = $this->debugStack->dump();
        $lines = explode(chr(10), $result);
        self::assertEquals('Title @ debug/nullWithTitle<Neos.Fusion:Debug>.value', $lines[0]);
        self::assertEquals('NULL', $lines[1]);
    }

    /**
     * @test
     */
    public function debugEelExpression()
    {
        $view = $this->buildView();
        $view->setFusionPath('debug/eelExpression');
        $view->render();
        $result = $this->debugStack->dump();
        $lines = explode(chr(10), $result);
        self::assertEquals('string "hello world" (11)', $lines[1]);
    }

    /**
     * @test
     */
    public function debugFusionObjectExpression()
    {
        $view = $this->buildView();
        $view->setFusionPath('debug/fusionObjectExpression');
        $view->render();
        $result = $this->debugStack->dump();
        $lines = explode(chr(10), $result);
        self::assertEquals('string "hello world" (11)', $lines[1]);
    }

    /**
     * @test
     */
    public function debugMultipleValues()
    {
        $view = $this->buildView();
        $view->setFusionPath('debug/multipleValues');
        $view->render();
        $result = $this->debugStack->dump();
        $lines = explode(chr(10), $result);
        self::assertEquals('@ debug/multipleValues<Neos.Fusion:Debug>.foo', $lines[0]);
        self::assertEquals('string "foo" (3)', $lines[1]);
        self::assertEquals('@ debug/multipleValues<Neos.Fusion:Debug>.bar', $lines[3]);
        self::assertEquals('string "bar" (3)', $lines[4]);
    }
}
