<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects;

use Neos\Fusion\Exception as FusionException;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the UriBuilder object
 */
class ActionUriTest extends AbstractFusionObjectTest
{
    /**
     * @test
     */
    public function buildRelativeUriToAction()
    {
        $this->registerRoute(
            'Fusion functional test',
            'neos/flow/test/http/foo',
            [
                '@package' => 'Neos.Flow',
                '@subpackage' => 'Tests\Functional\Http\Fixtures',
                '@controller' => 'Foo',
                '@action' => 'index',
                '@format' => 'html'
            ]
        );

        $view = $this->buildView();
        $view->setFusionPath('actionUri/foo');
        self::assertStringContainsString('/neos/flow/test/http/foo', $view->render());
    }

    /**
     * @test
     */
    public function buildRelativeUriToActionWithQueryParameters(): void
    {
        $this->registerRoute(
            'Fusion functional test: buildRelativeUriToActionWithQueryParameters',
            'neos/flow/test/http/withQueryParameters',
            [
                '@package' => 'Neos.Flow',
                '@controller' => 'Foo',
                '@action' => 'index',
                '@format' => 'html'
            ]
        );

        $view = $this->buildView();
        $view->setFusionPath('actionUri/withQueryParameters');
        self::assertSame('/neos/flow/test/http/withqueryparameters?foo=bar&bar%5Bbaz%5D=foos', $view->render());
    }

    /**
     * @test
     */
    public function buildRelativeUriToActionWithSectionAndQueryParameters(): void
    {
        $this->registerRoute(
            'Fusion functional test: buildRelativeUriToActionWithSectionAndQueryParameters',
            'neos/flow/test/http/withQueryParameters',
            [
                '@package' => 'Neos.Flow',
                '@controller' => 'Foo',
                '@action' => 'index',
                '@format' => 'html'
            ]
        );

        $view = $this->buildView();
        $view->setFusionPath('actionUri/withSectionAndQueryParameters');
        self::assertSame('/neos/flow/test/http/withqueryparameters?foo=bar#someSection', $view->render());
    }

    /**
     * @test
     */
    public function buildRelativeUriToActionWithExceedingArgumentsAndQueryParameters(): void
    {
        $this->registerRoute(
            'Fusion functional test: buildRelativeUriToActionWithExceedingArgumentsAndQueryParameters',
            'neos/flow/test/http/withExceedingArgumentsAndQueryParameters',
            [
                '@package' => 'Neos.Flow',
                '@controller' => 'Foo',
                '@action' => 'index',
                '@format' => 'html',
            ],
            appendExceedingArguments: true,
        );

        $view = $this->buildView();
        $view->setFusionPath('actionUri/withExceedingArgumentsAndQueryParameters');

        $this->expectException(FusionException::class);
        $this->expectExceptionMessage('"queryParameters" must not be used for Routes with "appendExceedingArguments"');
        $view->render();
    }
}
