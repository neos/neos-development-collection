<?php
namespace Neos\Fusion\Tests\Unit\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\FusionObjects\TagImplementation;

/**
 * Testcase for the Fusion Tag object
 */
class TagImplementationTest extends UnitTestCase
{
    /**
     * @var Runtime
     */
    protected $mockRuntime;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
    }

    public function tagExamples()
    {
        return [
            'default properties' => [[], null, null, '<div></div>'],
            'omit closing tag' => [['omitClosingTag' => true], null, null, '<div>'],
            'force self closing tag' => [['selfClosingTag' => true], null, null, '<div />'],
            'auto self closing tag' => [['tagName' => 'input'], ' type="text"', null, '<input type="text" />'],
            'tag name with content' => [['tagName' => 'h1'], null, 'Foo', '<h1>Foo</h1>'],
            'tag with attribute' => [['tagName' => 'link'], ' type="text/css" rel="stylesheet"', null, '<link type="text/css" rel="stylesheet" />'],
            'tag with array of classes' => [['tagName' => 'div'], ' class="icon icon-neos"', null, '<div class="icon icon-neos"></div>']
        ];
    }

    /**
     * @test
     * @dataProvider tagExamples
     */
    public function evaluateTests($properties, $attributes, $content, $expectedOutput)
    {
        $path = 'tag/test';
        $this->mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($evaluatePath, $that) use ($properties, $path, $attributes, $content) {
            $relativePath = str_replace($path . '/', '', $evaluatePath);
            switch ($relativePath) {
                case 'attributes':
                    return $attributes;
                case 'content':
                    return $content;
            }
            return isset($properties[$relativePath]) ? $properties[$relativePath] : null;
        }));

        $fusionObjectName = 'Neos.Fusion:Tag';
        $renderer = new TagImplementation($this->mockRuntime, $path, $fusionObjectName);

        $result = $renderer->evaluate();
        self::assertEquals($expectedOutput, $result);
    }
}
