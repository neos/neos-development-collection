<?php

declare(strict_types=1);

namespace Neos\Fusion\Tests\Functional\FusionObjects;

use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception\RuntimeException;

/**
 * TODO THIS IS HACKY AS WE CREATE A SHALLOW ABSTRACTION FOR TESTING
 *
 * We do that as the FusionView (rightfully) doesn't return mixed anymore - but we test arbitrary data.
 * {@see https://github.com/neos/neos-development-collection/pull/4856}
 *
 * We could instead also rewrite all tests to use the Runtime natively.
 * But that would be a lot of effort and diff for nothing.
 *
 * Instead we want to refactor our fusion tests to behat at some point.
 *
 * Thus the (temporary) hack / abstraction.
 *
 * @deprecated todo rewrite everything as behat test :D
 */
class TestingViewForFusionRuntime
{
    private string $fusionPath;

    public function __construct(
        private readonly Runtime $runtime
    ) {
    }

    public function setFusionPath(string $fusionPath)
    {
        $this->fusionPath = $fusionPath;
    }

    public function assign($key, $value)
    {
        $this->runtime->pushContext($key, $value);
    }

    public function setOption($key, $value)
    {
        match ($key) {
            'enableContentCache' => $this->runtime->setEnableContentCache($value),
            'debugMode' => $this->runtime->setDebugMode($value)
        };
    }

    public function assignMultiple(array $values)
    {
        foreach ($values as $key => $value) {
            $this->runtime->pushContext($key, $value);
        }
    }

    public function render()
    {
        try {
            return $this->runtime->render($this->fusionPath);
        } catch (RuntimeException $e) {
            throw $e->getWrappedException();
        }
    }
}
