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
use Neos\Fusion\FusionObjects\ArrayImplementation;

/**
 * Testcase for the TypoScript Array object
 */
class ArrayImplementationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function evaluateWithEmptyArrayRendersNull()
    {
        $mockTsRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $path = 'array/test';
        $typoScriptObjectName = 'Neos.Fusion:Array';
        $renderer = new ArrayImplementation($mockTsRuntime, $path, $typoScriptObjectName);
        $result = $renderer->evaluate();
        $this->assertNull($result);
    }

    /**
     * @return array
     */
    public function positionalSubElements()
    {
        return array(
            array(
                'Position end should put element to end',
                array('second' => array('__meta' => array('position' => 'end')), 'first' => array()),
                array('/first', '/second')
            ),
            array(
                'Position start should put element to start',
                array('second' => array(), 'first' => array('__meta' => array('position' => 'start'))),
                array('/first', '/second')
            ),
            array(
                'Position start should respect priority',
                array('second' => array('__meta' => array('position' => 'start 50')), 'first' => array('__meta' => array('position' => 'start 52'))),
                array('/first', '/second')
            ),
            array(
                'Position end should respect priority',
                array('second' => array('__meta' => array('position' => 'end 17')), 'first' => array('__meta' => array('position' => 'end'))),
                array('/first', '/second')
            ),
            array(
                'Positional numbers are in the middle',
                array('last' => array('__meta' => array('position' => 'end')), 'second' => array('__meta' => array('position' => '17')), 'first' => array('__meta' => array('position' => '5')), 'third' => array('__meta' => array('position' => '18'))),
                array('/first', '/second', '/third', '/last')
            ),
            array(
                'Position before adds before named element if present',
                array('second' => array(), 'first' => array('__meta' => array('position' => 'before second'))),
                array('/first', '/second')
            ),
            array(
                'Position before adds after start if named element not present',
                array('third' => array(), 'second' => array('__meta' => array('position' => 'before third')), 'first' => array('__meta' => array('position' => 'before unknown'))),
                array('/first', '/second', '/third')
            ),
            array(
                'Position before uses priority when referencing the same element; The higher the priority the closer before the element gets added.',
                array('third' => array(), 'second' => array('__meta' => array('position' => 'before third 12')), 'first' => array('__meta' => array('position' => 'before third'))),
                array('/first', '/second', '/third')
            ),
            array(
                'Position before works recursively',
                array('third' => array(), 'second' => array('__meta' => array('position' => 'before third')), 'first' => array('__meta' => array('position' => 'before second'))),
                array('/first', '/second', '/third')
            ),
            array(
                'Position after adds after named element if present',
                array('second' => array('__meta' => array('position' => 'after first')), 'first' => array()),
                array('/first', '/second')
            ),
            array(
                'Position after adds before end if named element not present',
                array('second' => array('__meta' => array('position' => 'after unknown')), 'third' => array('__meta' => array('position' => 'end')), 'first' => array()),
                array('/first', '/second', '/third')
            ),
            array(
                'Position after uses priority when referencing the same element; The higher the priority the closer after the element gets added.',
                array('third' => array('__meta' => array('position' => 'after first')), 'second' => array('__meta' => array('position' => 'after first 12')), 'first' => array()),
                array('/first', '/second', '/third')
            ),
            array(
                'Position after works recursively',
                array('third' => array('__meta' => array('position' => 'after second')), 'second' => array('__meta' => array('position' => 'after first')), 'first' => array()),
                array('/first', '/second', '/third')
            )
        );
    }

    /**
     * @test
     * @dataProvider positionalSubElements
     *
     * @param string $message
     * @param array $subElements
     * @param array $expectedKeyOrder
     */
    public function evaluateRendersKeysSortedByPositionMetaProperty($message, $subElements, $expectedKeyOrder)
    {
        $mockTsRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $mockTsRuntime->expects($this->any())->method('evaluate')->will($this->returnCallback(function ($path) use (&$renderedPaths) {
            $renderedPaths[] = $path;
        }));

        $path = '';
        $typoScriptObjectName = 'Neos.Fusion:Array';
        $renderer = new ArrayImplementation($mockTsRuntime, $path, $typoScriptObjectName);
        foreach ($subElements as $key => $value) {
            $renderer[$key] = $value;
        }
        $renderer->evaluate();

        $this->assertSame($expectedKeyOrder, $renderedPaths, $message);
    }
}
