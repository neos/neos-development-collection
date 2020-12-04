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
use Neos\Fusion\FusionObjects\JoinImplementation;

/**
 * Testcase for the Fusion Join object
 */
class JoinImplementationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function evaluateWithEmptyJoinRendersNull()
    {
        $mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
        $path = 'join/test';
        $fusionObjectName = 'Neos.Fusion:Join';
        $renderer = new JoinImplementation($mockRuntime, $path, $fusionObjectName);
        $result = $renderer->evaluate();
        self::assertNull($result);
    }

    /**
     * @return array
     */
    public function positionalSubElements()
    {
        return [
            [
                'Position end should put element to end',
                ['second' => ['__meta' => ['position' => 'end']], 'first' => ['__meta' => []]],
                ['/__meta/glue', '/first', '/second']
            ],
            [
                'Position start should put element to start',
                ['second' => ['__meta' => []], 'first' => ['__meta' => ['position' => 'start']]],
                ['/__meta/glue', '/first', '/second']
            ],
            [
                'Position start should respect priority',
                ['second' => ['__meta' => ['position' => 'start 50']], 'first' => ['__meta' => ['position' => 'start 52']]],
                ['/__meta/glue', '/first', '/second']
            ],
            [
                'Position end should respect priority',
                ['second' => ['__meta' => ['position' => 'end 17']], 'first' => ['__meta' => ['position' => 'end']]],
                ['/__meta/glue', '/first', '/second']
            ],
            [
                'Positional numbers are in the middle',
                ['last' => ['__meta' => ['position' => 'end']], 'second' => ['__meta' => ['position' => '17']], 'first' => ['__meta' => ['position' => '5']], 'third' => ['__meta' => ['position' => '18']]],
                ['/__meta/glue', '/first', '/second', '/third', '/last']
            ],
            [
                'Position before adds before named element if present',
                ['second' => ['__meta' => []], 'first' => ['__meta' => ['position' => 'before second']]],
                ['/__meta/glue', '/first', '/second']
            ],
            [
                'Position before uses priority when referencing the same element; The higher the priority the closer before the element gets added.',
                ['third' => ['__meta' => []], 'second' => ['__meta' => ['position' => 'before third 12']], 'first' => ['__meta' => ['position' => 'before third']]],
                ['/__meta/glue', '/first', '/second', '/third']
            ],
            [
                'Position before works recursively',
                ['third' => ['__meta' => []], 'second' => ['__meta' => ['position' => 'before third']], 'first' => ['__meta' => ['position' => 'before second']]],
                ['/__meta/glue', '/first', '/second', '/third']
            ],
            [
                'Position after adds after named element if present',
                ['second' => ['__meta' => ['position' => 'after first']], 'first' => ['__meta' => []]],
                ['/__meta/glue', '/first', '/second']
            ],
            [
                'Position after uses priority when referencing the same element; The higher the priority the closer after the element gets added.',
                ['third' => ['__meta' => ['position' => 'after first']], 'second' => ['__meta' => ['position' => 'after first 12']], 'first' => ['__meta' => []]],
                ['/__meta/glue', '/first', '/second', '/third']
            ],
            [
                'Position after works recursively',
                ['third' => ['__meta' => ['position' => 'after second']], 'second' => ['__meta' => ['position' => 'after first']], 'first' => ['__meta' => []]],
                ['/__meta/glue', '/first', '/second', '/third']
            ]
        ];
    }

    /**
     * @return array
     */
    public function positionalSubElementsThatShouldFailByInvalidPositions()
    {
        return [
            [
                'Position before adds after start if named element not present',
                ['third' => ['__meta' => []], 'second' => ['__meta' => ['position' => 'before third']], 'first' => ['__meta' => ['position' => 'before unknown']]],
                ['/__meta/glue', '/first', '/second', '/third']
            ],
            [
                'Position after adds before end if named element not present',
                ['second' => ['__meta' => ['position' => 'after unknown']], 'third' => ['__meta' => ['position' => 'end']], 'first' => ['__meta' => []]],
                ['/__meta/glue', '/first', '/second', '/third']
            ]
        ];
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
        $mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

        $mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($path) use (&$renderedPaths) {
            $renderedPaths[] = $path;
        }));

        $path = '';
        $fusionObjectName = 'Neos.Fusion:Join';
        $renderer = new JoinImplementation($mockRuntime, $path, $fusionObjectName);
        foreach ($subElements as $key => $value) {
            $renderer[$key] = $value;
        }
        $renderer->evaluate();

        self::assertSame($expectedKeyOrder, $renderedPaths, $message);
    }

    /**
     * @test
     * @dataProvider positionalSubElementsThatShouldFailByInvalidPositions
     *
     * @param string $message
     * @param array $subElements
     * @param array $expectedKeyOrder
     */
    public function evaluateRendersKeysSortedByPositionMetaPropertyThatShouldFail($message, $subElements, $expectedKeyOrder)
    {
        try {
            $mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();

            $mockRuntime->expects(self::any())->method('evaluate')->will(self::returnCallback(function ($path) use (&$renderedPaths) {
                $renderedPaths[] = $path;
            }));

            $path = '';
            $fusionObjectName = 'Neos.Fusion:Join';
            $renderer = new JoinImplementation($mockRuntime, $path, $fusionObjectName);
            foreach ($subElements as $key => $value) {
                $renderer[$key] = $value;
            }
            $renderer->evaluate();
            self::fail('Expected InvalidPositionException exception not thrown');
        } catch (\Exception $exception) {
            self::assertEquals($exception->getCode(), 1345126502);
        }
    }
}
