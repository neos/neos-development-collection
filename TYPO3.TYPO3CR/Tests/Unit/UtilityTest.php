<?php
namespace TYPO3\TYPO3CR\Tests\Unit;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
