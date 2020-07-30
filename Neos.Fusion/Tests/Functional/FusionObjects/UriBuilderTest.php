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

/**
 * Testcase for the UriBuilder object
 */
class UriBuilderTest extends AbstractFusionObjectTest
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
        $view->setFusionPath('uriBuilder/foo');
        self::assertStringContainsString('/neos/flow/test/http/foo', $view->render());
    }
}
