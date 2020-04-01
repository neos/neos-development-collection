<?php
namespace Neos\Fusion\Tests\Benchmark;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\CompilingEvaluator;

/**
 * A benchmark to test the Fusion runtime
 *
 * @BeforeMethods({"init"})
 */
class RuntimeBench
{

    /**
     * @var \Neos\Fusion\Core\Runtime
     */
    protected $runtime;

    public function init()
    {
        $fusionConfiguration = [
            'obj' => [
                '__objectType' => 'Neos.Fusion:Value',
                '__value' => null,
                '__eelExpression' => null,
                'value' => 'Hello obj!'
            ],
            'value' => [
                '__objectType' => null,
                '__value' => 'Hello value!',
                '__eelExpression' => null
            ],
            'expr' => [
                '__objectType' => null,
                '__value' => null,
                '__eelExpression' => 'foo'
            ],
            'longpath' => [
                '__objectType' => null,
                '__value' => null,
                '__eelExpression' => null,
                'sub' => [
                    '__objectType' => null,
                    '__value' => null,
                    '__eelExpression' => null,
                    'sub' => [
                        '__objectType' => null,
                        '__value' => null,
                        '__eelExpression' => null,
                        'sub' => [
                            '__objectType' => null,
                            '__value' => null,
                            '__eelExpression' => null,
                            'sub' => [
                                '__objectType' => null,
                                '__value' => null,
                                '__eelExpression' => null,
                                'sub' => [
                                    '__objectType' => null,
                                    '__value' => null,
                                    '__eelExpression' => null,
                                    'sub' => [
                                        '__objectType' => null,
                                        '__value' => null,
                                        '__eelExpression' => null,
                                        'value' => [
                                            '__objectType' => null,
                                            '__value' => 'Hello longpath!',
                                            '__eelExpression' => null
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '__prototypes' => [
                'Neos.Fusion:Value' => [
                    '__meta' => [
                        'class' => \Neos\Fusion\FusionObjects\ValueImplementation::class
                    ]
                ]
            ]
        ];
        $runtimeFactory = new \Neos\Fusion\Core\RuntimeFactory();
        $this->runtime = $runtimeFactory->create($fusionConfiguration);

        // Build an EEL evaluator suitable for benchmarking
        $evaluator = $this->buildEelEvaluator();
        \Neos\Utility\ObjectAccess::setProperty($this->runtime, 'eelEvaluator', $evaluator, true);

        $this->runtime->pushContextArray([
            'foo' => 'Hello expression!'
        ]);
    }

    /**
     * Benchmark evaluation of a path that is an object
     *
     * @Iterations(10)
     * @Revs(10000)
     */
    public function bench_evaluate_obj()
    {
        $x = $this->runtime->evaluate('obj');
        if ($x !== 'Hello obj!') {
            throw new \Exception('assertion failed');
        }
    }

    /**
     * Benchmark evaluation of a path that is a value
     *
     * @Iterations(10)
     * @Revs(10000)
     */
    public function bench_evaluate_value()
    {
        $x = $this->runtime->evaluate('value');
        if ($x !== 'Hello value!') {
            throw new \Exception('assertion failed');
        }
    }

    /**
     * Benchmark evaluation of a path that is an expression
     *
     * @Iterations(10)
     * @Revs(10000)
     */
    public function bench_evaluate_expr()
    {
        $x = $this->runtime->evaluate('expr');
        if ($x !== 'Hello expression!') {
            throw new \Exception('assertion failed');
        }
    }

    /**
     * Benchmark evaluation of a long path to make sure caching of effective configuration is correct
     *
     * @Iterations(10)
     * @Revs(10000)
     */
    public function bench_evaluate_longpath_value()
    {
        $x = $this->runtime->evaluate('longpath/sub/sub/sub/sub/sub/sub/value');
        if ($x !== 'Hello longpath!') {
            throw new \Exception('assertion failed');
        }
    }

    private function buildEelEvaluator(): CompilingEvaluator
    {
        $evaluator = new CompilingEvaluator();

        $backend = new \Neos\Cache\Backend\TransientMemoryBackend();
        $frontend = new \Neos\Cache\Frontend\StringFrontend('expressions', $backend);
        $backend->setCache($frontend);

        $evaluator->injectExpressionCache($frontend);
        return $evaluator;
    }
}
