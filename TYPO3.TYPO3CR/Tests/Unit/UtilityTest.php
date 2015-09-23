<?php
namespace TYPO3\TYPO3CR\Tests\Unit;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TYPO3CR\Utility;

/**
 */
class UtilityTest extends UnitTestCase
{
    /**
     * A data provider returning titles and the expected valid node names based on those.
     *
     * @return array
     */
    public function sourcesAndNodeNames()
    {
        return array(
            array('Überlandstraßen; adé', 'uberlandstrassen-ade'),
            array('Что делать, если я не хочу, UTF-8?', 'chto-delat-esli-ya-ne-hochu-utf-8'),
            array('TEST DRIVE: INFINITI Q50S 3.7', 'test-drive-infiniti-q50s-3-7')
        );
    }

    /**
     * @test
     * @dataProvider sourcesAndNodeNames
     */
    public function renderValidNodeNameWorks($source, $expectedNodeName)
    {
        $this->assertEquals($expectedNodeName, Utility::renderValidNodeName($source));
    }
}
