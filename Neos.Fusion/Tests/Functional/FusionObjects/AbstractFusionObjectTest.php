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
use Neos\Fusion\Core\RuntimeFactory;

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

    protected function buildView(): TestingViewForFusionRuntime
    {
        $this->request = ActionRequest::fromHttpRequest(new ServerRequest('GET', 'http://localhost/'));

        $runtime = $this->objectManager->get(RuntimeFactory::class)->createFromSourceCode(
            FusionSourceCodeCollection::fromFilePath(__DIR__ . '/Fixtures/Fusion/Root.fusion'),
            FusionGlobals::fromArray(['request' => $this->request])
        );

        $view = new TestingViewForFusionRuntime($runtime);
        $view->assign('fixtureDirectory', __DIR__ . '/Fixtures/');
        return $view;
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
