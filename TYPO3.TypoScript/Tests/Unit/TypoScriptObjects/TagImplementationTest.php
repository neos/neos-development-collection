<?php
namespace TYPO3\TypoScript\Tests\Unit\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\TypoScriptObjects\TagImplementation;

/**
 * Testcase for the TypoScript Tag object
 */
class TagImplementationTest extends UnitTestCase
{
    /**
     * @var Runtime
     */
    protected $mockTsRuntime;

    public function setUp()
    {
        parent::setUp();
        $this->mockTsRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
    }

    public function tagExamples()
    {
        return array(
            'default properties' => array(array(), null, null, '<div></div>'),
            'omit closing tag' => array(array('omitClosingTag' => true), null, null, '<div>'),
            'force self closing tag' => array(array('selfClosingTag' => true), null, null, '<div />'),
            'auto self closing tag' => array(array('tagName' => 'input'), ' type="text"', null, '<input type="text" />'),
            'tag name with content' => array(array('tagName' => 'h1'), null, 'Foo', '<h1>Foo</h1>'),
            'tag with attribute' => array(array('tagName' => 'link'), ' type="text/css" rel="stylesheet"', null, '<link type="text/css" rel="stylesheet" />'),
            'tag with array of classes' => array(array('tagName' => 'div'), ' class="icon icon-neos"', null, '<div class="icon icon-neos"></div>')
        );
    }

    /**
     * @test
     * @dataProvider tagExamples
     */
    public function evaluateTests($properties, $attributes, $content, $expectedOutput)
    {
        $path = 'tag/test';
        $this->mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($evaluatePath, $that) use ($properties, $path, $attributes, $content) {
            $relativePath = str_replace($path . '/', '', $evaluatePath);
            switch ($relativePath) {
                case 'attributes':
                    return $attributes;
                case 'content':
                    return $content;
            }
            return isset($properties[$relativePath]) ? $properties[$relativePath] : null;
        }));

        $typoScriptObjectName = 'TYPO3.TypoScript:Tag';
        $renderer = new TagImplementation($this->mockTsRuntime, $path, $typoScriptObjectName);

        $result = $renderer->evaluate();
        $this->assertEquals($expectedOutput, $result);
    }
}
