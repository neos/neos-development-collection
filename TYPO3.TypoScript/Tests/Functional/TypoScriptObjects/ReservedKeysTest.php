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
 * Testcase for reserved TypoScript keys
 *
 */
class ReservedKeysTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     * @expectedException \TYPO3\TypoScript\Exception
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
