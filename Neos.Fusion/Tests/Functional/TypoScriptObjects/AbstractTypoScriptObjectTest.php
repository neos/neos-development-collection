<?php
namespace Neos\Fusion\Tests\Functional\TypoScriptObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Fusion\View\TypoScriptView;

/**
 * Testcase for the TypoScript View
 *
 */
abstract class AbstractTypoScriptObjectTest extends FunctionalTestCase
{
    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * Helper to build a TypoScript view object
     *
     * @return TypoScriptView
     */
    protected function buildView()
    {
        $view = new TypoScriptView();

        $httpRequest = Request::createFromEnvironment();
        $request = $httpRequest->createActionRequest();

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        $this->controllerContext = new ControllerContext(
            $request,
            new Response(),
            new Arguments(array()),
            $uriBuilder
        );

        $view->setControllerContext($this->controllerContext);
        $view->disableFallbackView();
        $view->setPackageKey('Neos.Fusion');
        $view->setTypoScriptPathPattern(__DIR__ . '/Fixtures/TypoScript');
        $view->assign('fixtureDirectory', __DIR__ . '/Fixtures/');

        return $view;
    }

    /**
     * Used for TypoScript objects / Eel and plain value interoperability testing.
     * Renders TypoScripts in the following paths and expects given $expected as result each time:
     * $basePath . 'TypoScript'
     * $basePath . 'Eel'
     * $basePath . 'PlainValue'
     *
     * @param string $expected
     * @param string $basePath
     */
    protected function assertMultipleTypoScriptPaths($expected, $basePath)
    {
        $this->assertTyposcriptPath($expected, $basePath . 'Eel');
        $this->assertTyposcriptPath($expected, $basePath . 'PlainValue');
        $this->assertTyposcriptPath($expected, $basePath . 'TypoScript');
    }

    /**
     * Renders the given TypoScript path and asserts that the result is the same es the given expected.
     *
     * @param string $expected
     * @param string $path
     */
    protected function assertTypoScriptPath($expected, $path)
    {
        $view = $this->buildView();
        $view->setTypoScriptPath($path);
        $this->assertSame($expected, $view->render(), 'TypoScript at path "' . $path . '" produced wrong results.');
    }
}
