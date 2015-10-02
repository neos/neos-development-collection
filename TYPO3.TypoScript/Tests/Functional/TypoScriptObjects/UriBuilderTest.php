<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
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
class UriBuilderTest extends AbstractTypoScriptObjectTest
{
    /**
     * @test
     */
    public function buildRelativeUriToAction()
    {
        $this->registerRoute(
            'TypoScript functional test',
            'typo3/flow/test/http/foo',
            array(
                '@package' => 'TYPO3.Flow',
                '@subpackage' => 'Tests\Functional\Http\Fixtures',
                '@controller' => 'Foo',
                '@action' => 'index',
                '@format' => 'html'
            ));

        $view = $this->buildView();
        $view->setTypoScriptPath('uriBuilder/foo');
        $this->assertContains('/typo3/flow/test/http/foo', $view->render());
    }
}
