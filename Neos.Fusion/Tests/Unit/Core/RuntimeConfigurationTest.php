<?php

namespace Neos\Fusion\Tests\Unit\Core;

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

class RuntimeConfigurationTest extends UnitTestCase
{

    /**
     * @test
     */
    public function forPath_caches_paths()
    {
        $fusionConfiguration = [
            '__prototypes' => [
                'MyComponent1' => [
                    'foo' => 'Bar'
                ],
                'MyComponent2' => [
                    'foo' => 'Baz'
                ]
            ],
            'root' => [
                '__objectType' => 'Case',
                'item1' => [
                    'condition' => [
                        '__eelExpression' => 'foo == "bar"'
                    ],
                    'renderer' => [
                        '__objectType' => 'MyComponent1',
                    ]
                ],
                'item2' => [
                    'condition' => [
                        '__eelExpression' => 'foo == "baz"'
                    ],
                    'renderer' => [
                        '__objectType' => 'MyComponent2',
                    ]
                ]
            ]
        ];
        $runtimeConfiguration = new \Neos\Fusion\Core\RuntimeConfiguration($fusionConfiguration);
        $configuration = $runtimeConfiguration->forPath('root/item1/renderer');

        $this->assertEquals([
            'foo' => 'Bar',
            '__objectType' => 'MyComponent1'
        ], $configuration);

        $this->assertTrue($runtimeConfiguration->isPathCached('root/item1/renderer'), 'Path "root/item1/renderer" should be cached');
        $this->assertTrue($runtimeConfiguration->isPathCached('root/item1'), 'Path "root/item1" should be cached');
        $this->assertTrue($runtimeConfiguration->isPathCached('root'), 'Path "root" should be cached');
    }
}
