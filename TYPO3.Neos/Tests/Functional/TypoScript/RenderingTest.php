<?php
namespace TYPO3\Neos\Tests\Functional\TypoScript;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Neos\Tests\Functional\AbstractNodeTest;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Functional test case which tests the rendering
 *
 * @group large
 */
class RenderingTest extends AbstractNodeTest
{
    /**
     * @test
     */
    public function basicRenderingWorks()
    {
        $output = $this->simulateRendering();

        $this->assertTeaserConformsToBasicRendering($output);
        $this->assertMainContentConformsToBasicRendering($output);
        $this->assertSidebarConformsToBasicRendering($output);
    }

    /**
     * @test
     */
    public function debugModeSettingWorks()
    {
        $output = $this->simulateRendering(null, true);
        $this->assertContains('<!-- Beginning to render TS path', $output);

        $output = $this->simulateRendering();
        $this->assertNotContains('<!-- Beginning to render TS path', $output);
    }

    /**
     * @test
     */
    public function overriddenValueInPrototype()
    {
        $output = $this->simulateRendering('Test_OverriddenValueInPrototype.ts2');

        $this->assertTeaserConformsToBasicRendering($output);
        $this->assertMainContentConformsToBasicRendering($output);

        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-headline > div', 'Static Headline', true, $output);
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-text > div', 'Below, you\'ll see the most recent activity', true, $output);
        $this->assertSelectEquals('.sidebar', '[COMMIT WIDGET]', true, $output);
    }

    /**
     * @test
     */
    public function additionalProcessorInPrototype()
    {
        $output = $this->simulateRendering('Test_AdditionalProcessorInPrototype.ts2');

        $this->assertTeaserConformsToBasicRendering($output);
        $this->assertMainContentConformsToBasicRendering($output);

        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > .processor-wrap', 'BEFOREStatic HeadlineAFTER', true, $output);
    }

    /**
     * @test
     */
    public function additionalProcessorInPrototype2()
    {
        $output = $this->simulateRendering('Test_AdditionalProcessorInPrototype2.ts2');

        $this->assertSelectEquals('.teaser > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > header > h1', 'Welcome to this example', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > header > h1', 'Documentation', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .center > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > header > h1', 'Development Process', true, $output);

        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > header > .processor-wrap', 'BEFOREStatic HeadlineAFTER', true, $output);
    }

    /**
     * @test
     */
    public function replaceElementRenderingCompletelyInSidebar()
    {
        $output = $this->simulateRendering('Test_ReplaceElementRenderingCompletelyInSidebar.ts2');
        $this->assertTeaserConformsToBasicRendering($output);
        $this->assertMainContentConformsToBasicRendering($output);

            // header is now wrapped in h3
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-headline > header > h3', 'Last Commits', true, $output);
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-text > div', 'Below, you\'ll see the most recent activity', true, $output);
    }

    /**
     * @test
     */
    public function prototypeInheritance()
    {
        $output = $this->simulateRendering('Test_PrototypeInheritance.ts2');
        $this->assertSelectEquals('.teaser > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > h1', 'Static Headline', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > h1', 'Static Headline', true, $output);

            // header is now wrapped in h3 (as set in the concrete template), AND is set to a static headline (as set in the abstract template)
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > h1', 'Static Headline', true, $output);
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-text > div', 'Below, you\'ll see the most recent activity', true, $output);
    }

    /**
     * @test
     */
    public function replaceElementRenderingCompletelyBasedOnAdvancedCondition()
    {
        $output = $this->simulateRendering('Test_ReplaceElementRenderingCompletelyBasedOnAdvancedCondition.ts2');
        $this->assertTeaserConformsToBasicRendering($output);
        $this->assertSidebarConformsToBasicRendering($output);

        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > header', 'DOCS: Documentation', true, $output);
    }

    /**
     * @test
     */
    public function overriddenValueInNestedPrototype()
    {
        $output = $this->simulateRendering('Test_OverriddenValueInNestedPrototype.ts2');
        $this->assertTeaserConformsToBasicRendering($output);

        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > header', 'Static Headline', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .center > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > header', 'Static Headline', true, $output);

        $this->assertSidebarConformsToBasicRendering($output);
    }

    /**
     * @test
     */
    public function overriddenValueInNestedPrototype2()
    {
        $output = $this->simulateRendering('Test_OverriddenValueInNestedPrototype2.ts2');
        $this->assertTeaserConformsToBasicRendering($output);

        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > header', 'Static Headline', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .center > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > h1', 'Development Process', true, $output);

        $this->assertSidebarConformsToBasicRendering($output);
    }

    /**
     * @test
     */
    public function contentCollectionsAndWrappedContentElementsCanBeRenderedWithCustomTagsAndAttributes()
    {
        $output = $this->simulateRendering();

        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-list > ul.my-list > li.my-list-item > p', 'First', true, $output);
    }

    /**
     * @test
     */
    public function menuIsRenderedAsExpected()
    {
        $output = $this->simulateRendering();
        $this->assertSelectEquals('.navigation > ul > li.normal > a', 'Frameworks', true, $output);
    }

    /**
     * @test
     */
    public function classesAreAppendedAsExpected()
    {
        $output = $this->simulateRendering('Test_AppendingClassesToContent.ts2');
        $this->assertSelectEquals('.teaser > .neos-contentcollection > .typo3-neos-nodetypes-headline.test h1', 'Welcome to this example', true, $output);
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-headline.test h1', 'Last Commits', true, $output);
    }

    /**
     * @test
     */
    public function menuWithNegativeEntryLevelIsRenderedAsExpected()
    {
        $output = $this->simulateRendering('Test_MenuNegativeEntryLevel.ts2');
        $this->assertSelectEquals('.navigation > ul > li.normal > a', 'About Us', true, $output);
        $this->assertSelectEquals('.navigation > ul > li.active > a', 'Products', true, $output);
    }

    /**
     * Helper function for setting assertions
     * @param string $output
     */
    protected function assertTeaserConformsToBasicRendering($output)
    {
        $this->assertContains('This website is powered by Neos, the Open Source Content Application Platform licensed under the GNU/GPL.', $output);
        $this->assertSelectEquals('h1', 'Home', true, $output);

        $this->assertSelectEquals('.teaser > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > h1', 'Welcome to this example', true, $output);
        $this->assertSelectEquals('.teaser > .neos-contentcollection > .typo3-neos-nodetypes-text > div', 'This is our exemplary rendering test.', true, $output);
    }

    /**
     * Helper function for setting assertions
     * @param string $output
     */
    protected function assertMainContentConformsToBasicRendering($output)
    {
        $this->assertSelectEquals('.main > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > h1', 'Do you love TYPO3 Flow?', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .typo3-neos-nodetypes-text > div', 'If you do, make sure to post your opinion about it on Twitter!', true, $output);

        $this->assertSelectEquals('.main', '[TWITTER WIDGET]', true, $output);

        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > h1', 'Documentation', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .typo3-neos-nodetypes-text > div', 'We\'re still improving our docs, but check them out nevertheless!', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left', '[SLIDESHARE]', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .center > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > h1', 'Development Process', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .center > .neos-contentcollection > .typo3-neos-nodetypes-text > div', 'We\'re spending lots of thought into our infrastructure, you can profit from that, too!', true, $output);
    }

    /**
     * Helper function for setting assertions
     * @param string $output
     */
    protected function assertSidebarConformsToBasicRendering($output)
    {
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-headline > div > h1', 'Last Commits', true, $output);
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .typo3-neos-nodetypes-text > div', 'Below, you\'ll see the most recent activity', true, $output);
        $this->assertSelectEquals('.sidebar', '[COMMIT WIDGET]', true, $output);
    }

    /**
     * Helper function for setting assertions
     * @static
     * @param string $selector
     * @param string $content
     * @param integer|bool $count assert specific number of elements, assert any elements exist if true, assert no elements exist if false
     * @param mixed $actual
     * @param string $message
     * @param boolean $isHtml
     */
    public static function assertSelectEquals($selector, $content, $count, $actual, $message = '', $isHtml = true)
    {
        if ($message === '') {
            $message = $selector . ' did not match: ' . $actual;
        }

        $crawler = new Crawler;
        if ($actual instanceof \DOMDocument) {
            $crawler->addDocument($actual);
        } elseif ($isHtml) {
            $crawler->addHtmlContent($actual);
        } else {
            $crawler->addXmlContent($actual);
        }
        $crawler = $crawler->filter($selector);

        if (is_string($content)) {
            $crawler = $crawler->reduce(function (Crawler $node, $i) use ($content) {
                if ($content === '') {
                    return $node->text() === '';
                }
                if (preg_match('/^regexp\s*:\s*(.*)/i', $content, $matches)) {
                    return (bool) preg_match($matches[1], $node->text());
                }
                return strstr($node->text(), $content) !== false;
            });
        }

        $found = count($crawler);
        if (is_numeric($count)) {
            self::assertEquals($count, $found, $message);
        } elseif (is_bool($count)) {
            $found = $found > 0;
            if ($count) {
                self::assertTrue($found, $message);
            } else {
                self::assertFalse($found, $message);
            }
        } elseif (is_array($count) && (isset($count['>']) || isset($count['<']) || isset($count['>=']) || isset($count['<=']))) {
            if (isset($count['>'])) {
                self::assertTrue($found > $count['>'], $message);
            }
            if (isset($count['>='])) {
                self::assertTrue($found >= $count['>='], $message);
            }
            if (isset($count['<'])) {
                self::assertTrue($found < $count['<'], $message);
            }
            if (isset($count['<='])) {
                self::assertTrue($found <= $count['<='], $message);
            }
        } else {
            throw new \PHPUnit_Framework_Exception('Invalid count format');
        }
    }

    /**
     * Simulate the rendering
     *
     * @param string $additionalTypoScriptFile
     * @param boolean $debugMode
     * @return string
     */
    protected function simulateRendering($additionalTypoScriptFile = null, $debugMode = false)
    {
        $typoScriptRuntime = $this->createRuntimeWithFixtures($additionalTypoScriptFile);
        $typoScriptRuntime->setEnableContentCache(false);
        if ($debugMode) {
            $typoScriptRuntime->injectSettings(array('debugMode' => true, 'rendering' => array('exceptionHandler' => 'TYPO3\TypoScript\Core\ExceptionHandlers\ThrowingHandler')));
        }
        $contentContext = $this->node->getContext();
        if (!$contentContext instanceof \TYPO3\Neos\Domain\Service\ContentContext) {
            $this->fail('Node context must be of type ContentContext');
        }
        $typoScriptRuntime->pushContextArray(array(
            'node' => $this->node,
            'documentNode' => $this->node,
            'site' => $contentContext->getCurrentSiteNode(),
            'fixturesDirectory' => __DIR__ . '/Fixtures'
        ));
        $output = $typoScriptRuntime->render('page1');
        $typoScriptRuntime->popContext();

        return $output;
    }

    /**
     * Create a TypoScript runtime with the test base TypoScript and an optional additional fixture
     *
     * @param string $additionalTypoScriptFile
     * @return \TYPO3\TypoScript\Core\Runtime
     */
    protected function createRuntimeWithFixtures($additionalTypoScriptFile = null)
    {
        $typoScriptService = new \TYPO3\Neos\Domain\Service\TypoScriptService();
        $typoScriptService->setSiteRootTypoScriptPattern(__DIR__ . '/Fixtures/BaseTypoScript.ts2');

        if ($additionalTypoScriptFile !== null) {
            $typoScriptService->setAppendTypoScriptIncludes(array($additionalTypoScriptFile));
        }

        $controllerContext = $this->buildMockControllerContext();

        $runtime = $typoScriptService->createRuntime($this->node->getParent(), $controllerContext);

        return $runtime;
    }

    /**
     * @return \TYPO3\Flow\Mvc\Controller\ControllerContext
     */
    protected function buildMockControllerContext()
    {
        $httpRequest = \TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://foo.bar/bazfoo'));
        $request = new \TYPO3\Flow\Mvc\ActionRequest($httpRequest);
        $response = new \TYPO3\Flow\Http\Response();
        /** @var \TYPO3\Flow\Mvc\Controller\Arguments $mockArguments */
        $mockArguments = $this->getMock(\TYPO3\Flow\Mvc\Controller\Arguments::class, array(), array(), '', false);
        $uriBuilder = new \TYPO3\Flow\Mvc\Routing\UriBuilder();

        $controllerContext = new \TYPO3\Flow\Mvc\Controller\ControllerContext(
            $request,
            $response,
            $mockArguments,
            $uriBuilder,
            $this->getMock('TYPO3\Flow\Mvc\FlashMessageContainer')
        );
        return $controllerContext;
    }
}
