<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\SharedModel\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 */
class NodeNameTest extends TestCase
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
    public function transliterateFromStringWorksForNodeNames($source, $expectedNodeName)
    {
        Assert::assertEquals($expectedNodeName, NodeName::transliterateFromString($source)->value);
    }
}
