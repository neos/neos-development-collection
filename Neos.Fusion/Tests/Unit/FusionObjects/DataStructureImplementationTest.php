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
use Neos\Fusion\FusionObjects\DataStructureImplementation;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the Fusion Concat object
 */
class DataStructureImplementationTest extends UnitTestCase
{
    /**
     * @var Runtime|MockObject
     */
    private $mockRuntime;


    public function setUp(): void
    {
        $this->mockRuntime = $this->getMockBuilder(Runtime::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function evaluateWithEmptyArrayRendersEmptyArray(): void
    {
        $path = 'datastructure/test';
        $fusionObjectName = 'Neos.Fusion:DataStructure';
        $renderer = new DataStructureImplementation($this->mockRuntime, $path, $fusionObjectName);
        $result = $renderer->evaluate();
        self::assertSame($result, []);
    }

    /**
     * @return array
     */
    public function positionalSubElements(): array
    {
        return [
            [
                'Position end should put element to end',
                ['second' => ['__meta' => ['position' => 'end']], 'first' => ['__meta' => []]],
                ['/first', '/second']
            ],
            [
                'Position start should put element to start',
                ['second' => ['__meta' => []], 'first' => ['__meta' => ['position' => 'start']]],
                ['/first', '/second']
            ],
            [
                'Position start should respect priority',
                ['second' => ['__meta' => ['position' => 'start 50']], 'first' => ['__meta' => ['position' => 'start 52']]],
                ['/first', '/second']
            ],
            [
                'Position end should respect priority',
                ['second' => ['__meta' => ['position' => 'end 17']], 'first' => ['__meta' => ['position' => 'end']]],
                ['/first', '/second']
            ],
            [
                'Positional numbers are in the middle',
                ['last' => ['__meta' => ['position' => 'end']], 'second' => ['__meta' => ['position' => '17']], 'first' => ['__meta' => ['position' => '5']], 'third' => ['__meta' => ['position' => '18']]],
                ['/first', '/second', '/third', '/last']
            ],
            [
                'Position before adds before named element if present',
                ['second' => ['__meta' => []], 'first' => ['__meta' => ['position' => 'before second']]],
                ['/first', '/second']
            ],
            [
                'Position before uses priority when referencing the same element; The higher the priority the closer before the element gets added.',
                ['third' => ['__meta' => []], 'second' => ['__meta' => ['position' => 'before third 12']], 'first' => ['__meta' => ['position' => 'before third']]],
                ['/first', '/second', '/third']
            ],
            [
                'Position before works recursively',
                ['third' => ['__meta' => []], 'second' => ['__meta' => ['position' => 'before third']], 'first' => ['__meta' => ['position' => 'before second']]],
                ['/first', '/second', '/third']
            ],
            [
                'Position after adds after named element if present',
                ['second' => ['__meta' => ['position' => 'after first']], 'first' => ['__meta' => []]],
                ['/first', '/second']
            ],
            [
                'Position after uses priority when referencing the same element; The higher the priority the closer after the element gets added.',
                ['third' => ['__meta' => ['position' => 'after first']], 'second' => ['__meta' => ['position' => 'after first 12']], 'first' => ['__meta' => []]],
                ['/first', '/second', '/third']
            ],
            [
                'Position after works recursively',
                ['third' => ['__meta' => ['position' => 'after second']], 'second' => ['__meta' => ['position' => 'after first']], 'first' => ['__meta' => []]],
                ['/first', '/second', '/third']
            ]
        ];
    }

    /**
     * @test
     * @dataProvider positionalSubElements
     */
    public function evaluateRendersKeysSortedByPositionMetaProperty(string $message, array $subElements, array $expectedKeyOrder): void
    {
        $this->mockRuntime->method('evaluate')->willReturnCallback(function ($path) use (&$renderedPaths) {
            $renderedPaths[] = $path;
        });

        $fusionObjectName = 'Neos.Fusion:DataStructure';
        $renderer = new DataStructureImplementation($this->mockRuntime, '', $fusionObjectName);
        foreach ($subElements as $key => $value) {
            $renderer[$key] = $value;
        }
        $renderer->evaluate();

        self::assertSame($expectedKeyOrder, $renderedPaths, $message);
    }

    /**
     * @return array
     */
    public function positionalSubElementsThatShouldFailByInvalidPositions(): array
    {
        return [
            [
                ['second' => ['__meta' => ['position' => 'after unknown']], 'third' => ['__meta' => ['position' => 'end']], 'first' => ['__meta' => []]],
            ],
            [
                ['third' => ['__meta' => []], 'second' => ['__meta' => ['position' => 'before third']], 'first' => ['__meta' => ['position' => 'before unknown']]],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider positionalSubElementsThatShouldFailByInvalidPositions
     *
     * @param array $subElements
     */
    public function evaluateThrowsExceptionIfKeysSortedByPositionMetaPropertyContainsInvalidValues(array $subElements): void
    {
        $fusionObjectName = 'Neos.Fusion:DataStructure';
        $renderer = new DataStructureImplementation($this->mockRuntime, '', $fusionObjectName);
        foreach ($subElements as $key => $value) {
            $renderer[$key] = $value;
        }
        $this->expectExceptionCode(1345126502);

        $renderer->evaluate();
    }
}
