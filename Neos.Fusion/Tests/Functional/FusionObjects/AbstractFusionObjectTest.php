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

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Fusion\View\FusionView;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * Testcase for the Fusion View
 *
 */
abstract class AbstractFusionObjectTest extends FunctionalTestCase
{
    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * Helper to build a Fusion view object
     *
     * @return FusionView
     */
    protected function buildView()
    {
        $view = new FusionView();

        /** @var ServerRequestFactoryInterface $httpRequestFactory */
        $httpRequestFactory = $this->objectManager->get(ServerRequestFactoryInterface::class);
        $httpRequest = $httpRequestFactory->createServerRequest('GET', 'http://localhost/');
        $request = ActionRequest::fromHttpRequest($httpRequest);

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        $this->controllerContext = new ControllerContext(
            $request,
            new ActionResponse(),
            new Arguments([]),
            $uriBuilder
        );

        $view->setControllerContext($this->controllerContext);
        $view->setPackageKey('Neos.Fusion');
        $view->setFusionPathPattern(__DIR__ . '/Fixtures/Fusion');
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
