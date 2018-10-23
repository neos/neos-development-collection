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
 * Testcase for reserved Fusion keys
 *
 */
class ReservedKeysTest extends AbstractFusionObjectTest
{
    /**
     * @test
     * @expectedException \Neos\Fusion\Exception
     */
    public function usingReservedKeysThrowsException()
    {
        $view = $this->buildView();
        $view->setFusionPathPattern(__DIR__ . '/Fixtures/ReservedKeysFusion');
        $view->render();
    }

    /**
     * @test
     */
    public function nonReservedKeysWorks()
    {
        $view = $this->buildView();
        $view->setFusionPath('reservedKeys');
        $this->assertEquals($view->render(), ['__custom' => 1]);
    }
}
