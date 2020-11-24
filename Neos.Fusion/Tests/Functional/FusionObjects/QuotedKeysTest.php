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
 * Testcase for the Fusion DataStructure
 *
 */
class QuotedKeysTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function mulitpleKeysWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('quotedKeys/multipleKeys');
        $result = $view->render();

        self::assertSame(count($result), 6);
        foreach ($result as $key => $value) {
            self::assertSame($value, 1);
        }
    }

    /**
     * @test
     */
    public function singleQuotedWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('quotedKeys/single');
        self::assertSame($view->render(), 1);
    }

    /**
     * @test
     */
    public function doubleQuotedWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('quotedKeys/double');
        self::assertSame($view->render(), 1);
    }

    /**
     * @test
     */
    public function nestedQuotedWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('quotedKeys/nested/keys');
        self::assertSame($view->render(), 1);
    }

    /**
     * @test
     */
    public function specialCharactersWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('quotedKeys/@special/_!');
        self::assertSame($view->render(), 1);
    }

    /**
     * @test
     */
    public function prototypeAndQuotedKeysWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('quotedKeys/prototype/test');
        self::assertSame($view->render(), 'X1');
    }
}
