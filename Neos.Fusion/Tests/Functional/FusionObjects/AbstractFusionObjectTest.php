<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Fusion\Core\FusionGlobals;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Fusion\Exception\RuntimeException;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * Testcase for the Fusion View
 *
 */
abstract class AbstractFusionObjectTest extends FunctionalTestCase
{
    /**
     * @var ActionRequest
     */
    protected $request;

    /**
     * TODO THIS IS HACKY AS WE CREATE AN OWN VIEW
     *
     * We do that as the FusionView (rightfully) doesn't return mixed anymore.
     *
     * We could instead also rewrite all tests to use the Runtime instead.
     *
     * But that would be a lot of effort for nothing.
     *
     * Instead we want to refactor our tests to behat at some point.
     *
     * Thus the hack.
     */
    protected function buildView()
    {
        /** @var ServerRequestFactoryInterface $httpRequestFactory */
        $this->request = ActionRequest::fromHttpRequest(new ServerRequest('GET', 'http://localhost/'));

        $runtime = $this->objectManager->get(RuntimeFactory::class)->createFromSourceCode(
            FusionSourceCodeCollection::fromFilePath(__DIR__ . '/Fixtures/Fusion/Root.fusion'),
            FusionGlobals::fromArray(['request' => $this->request])
        );

        $runtime->pushContext('fixtureDirectory', __DIR__ . '/Fixtures/');

        // todo rewrite everything as behat test :D
        return new class($runtime) {
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
        };
    }

    /**
     * Used for Fusion objects / Eel and plain value interoperability testing.
     * Renders Fusions in the following paths and expects given $expected as result each time:
     * $basePath . 'Fusion'
     * $basePath . 'Eel'
     * $basePath . 'PlainValue'
     *
     * @param string $expected
     * @param string $basePath
     */
    protected function assertMultipleFusionPaths($expected, $basePath)
    {
        $this->assertFusionPath($expected, $basePath . 'Eel');
        $this->assertFusionPath($expected, $basePath . 'PlainValue');
        $this->assertFusionPath($expected, $basePath . 'Fusion');
    }

    /**
     * Renders the given Fusion path and asserts that the result is the same es the given expected.
     *
     * @param string $expected
     * @param string $path
     */
    protected function assertFusionPath($expected, $path)
    {
        $view = $this->buildView();
        $view->setFusionPath($path);
        self::assertSame($expected, $view->render(), 'Fusion at path "' . $path . '" produced wrong results.');
    }
}
