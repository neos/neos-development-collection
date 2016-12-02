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
 * Testcase for reserved TypoScript keys
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
        $view->setTypoScriptPathPattern(__DIR__ . '/Fixtures/ReservedKeysTypoScript');
        $view->render();
    }

    /**
     * @test
     */
    public function nonReservedKeysWorks()
    {
        $view = $this->buildView();
        $view->setTypoScriptPath('reservedKeys');
        $this->assertEquals($view->render(), array('__custom' => 1));
    }
}
