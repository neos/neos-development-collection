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
        $ds = '<Neos.Fusion:DataStructure>';
        return [
            [
                'Position end should put element to end',
                ['second' => ['__meta' => ['position' => 'end']], 'first' => []],
                ["/first$ds", "/second$ds"]
            ],
            [
                'Position start should put element to start',
                ['second' => [], 'first' => ['__meta' => ['position' => 'start']]],
                ["/first$ds", "/second$ds"]
            ],
            [
                'Position start should respect priority',
                ['second' => ['__meta' => ['position' => 'start 50']], 'first' => ['__meta' => ['position' => 'start 52']]],
                ["/first$ds", "/second$ds"]
            ],
            [
                'Position end should respect priority',
                ['second' => ['__meta' => ['position' => 'end 17']], 'first' => ['__meta' => ['position' => 'end']]],
                ["/first$ds", "/second$ds"]
            ],
            [
                'Positional numbers are in the middle',
                ['last' => ['__meta' => ['position' => 'end']], 'second' => ['__meta' => ['position' => '17']], 'first' => ['__meta' => ['position' => '5']], 'third' => ['__meta' => ['position' => '18']]],
                ["/first$ds", "/second$ds", "/third$ds", "/last$ds"]
            ],
            [
                'Position before adds before named element if present',
                ['second' => [], 'first' => ['__meta' => ['position' => 'before second']]],
                ["/first$ds", "/second$ds"]
            ],
            [
                'Position before uses priority when referencing the same element; The higher the priority the closer before the element gets added.',
                ['third' => [], 'second' => ['__meta' => ['position' => 'before third 12']], 'first' => ['__meta' => ['position' => 'before third']]],
                ["/first$ds", "/second$ds", "/third$ds"]
            ],
            [
                'Position before works recursively',
                ['third' => [], 'second' => ['__meta' => ['position' => 'before third']], 'first' => ['__meta' => ['position' => 'before second']]],
                ["/first$ds", "/second$ds", "/third$ds"]
            ],
            [
                'Position after adds after named element if present',
                ['second' => ['__meta' => ['position' => 'after first']], 'first' => []],
                ["/first$ds", "/second$ds"]
            ],
            [
                'Position after uses priority when referencing the same element; The higher the priority the closer after the element gets added.',
                ['third' => ['__meta' => ['position' => 'after first']], 'second' => ['__meta' => ['position' => 'after first 12']], 'first' => []],
                ["/first$ds", "/second$ds", "/third$ds"]
            ],
            [
                'Position after works recursively',
                ['third' => ['__meta' => ['position' => 'after second']], 'second' => ['__meta' => ['position' => 'after first']], 'first' => []],
                ["/first$ds", "/second$ds", "/third$ds"]
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
}
