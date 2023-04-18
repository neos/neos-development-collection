<?php

namespace Neos\SiteKickstarter\Tests\Unit\Service;

/*
 * This file is part of the Neos.SiteKickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\SiteKickstarter\Service\SimpleTemplateRenderer;

class SimpleTemplateRendererTest extends UnitTestCase
{
    /**
     * @var SimpleTemplateRenderer
     */
    protected $simpleTemplateRenderer;

    public function setUp(): void
    {
        $this->simpleTemplateRenderer = new SimpleTemplateRenderer();
    }

    /**
     * @test
     */
    public function replaceSimpleKeyInString()
    {
        $filePath = __DIR__ . '/Fixtures/Templates/simpleString.txt';
        $contextVariables = [
            'sitePackage' => 'Ninja.Turtles'
        ];

        $resultString = $this->simpleTemplateRenderer->render($filePath, $contextVariables);

        $this->assertTrue(strpos($resultString, 'Ninja.Turtles') > -1);
    }

    /**
     * Just a test to be sure nothing gets changed if the contextVariables array has no matching key
     *
     * @test
     */
    public function nothingGetsChangedInString()
    {
        $filePath = __DIR__ . '/Fixtures/Templates/simpleString.txt';
        $contextVariables = [
            'notInString' => 'Ninja.Turtles'
        ];

        $resultString = $this->simpleTemplateRenderer->render($filePath, $contextVariables);

        $this->assertTrue(strpos($resultString, 'Ninja.Turtles') === false);
        $this->assertTrue($resultString === file_get_contents($filePath));
    }
}
