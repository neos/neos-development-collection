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
        return [
            ['Überlandstraßen; adé', 'uberlandstrassen-ade'],
            ['TEST DRIVE: INFINITI Q50S 3.7', 'test-drive-infiniti-q50s-3-7'],
            ['汉语', 'yi-yu'],
            ['日本語', 'ri-ben-yu'],
            ['Việt', 'viet'],
            ['ភាសាខ្មែរ', 'bhaasaakhmaer'],
            ['ภาษาไทย', 'phaasaaaithy'],
            ['العَرَبِية', 'l-arabiy'],
            ['עברית', 'bryt'],
            ['한국어', 'hangugeo'],
            ['ελληνικά', 'ellenika'],
            ['မြန်မာဘာသာ', 'm-n-maabhaasaa'],
            [' हिन्दी', 'hindii'],
            [' x- ', 'x'],
            ['', 'node-' . md5('')],
            [',.~', 'node-' . md5(',.~')]
        ];
    }

    /**
     * @test
     * @dataProvider sourcesAndNodeNames
     */
    public function renderValidNodeNameWorks($source, $expectedNodeName)
    {
        $this->assertEquals($expectedNodeName, Utility::renderValidNodeName($source));
    }

    /**
     * @test
     */
    public function removeControlCharactersCleansStringButLeavesItIntact()
    {
        $simpleStringContent = 'Soemthing with control characters and other stuff #ä#+´´)(=?:_;ÄÖ*+ü ';
        $originalString = chr(9) . $simpleStringContent . chr(4) . chr(30) . chr(10);

        $result = Utility::removeControlCharactersFrom($originalString);
        $this->assertEquals(chr(9) . $simpleStringContent  . chr(10), $result);
    }
}
