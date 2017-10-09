<?php
namespace Neos\Neos\Tests\Functional\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\FlashMessageContainer;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\FusionService;
use Neos\Neos\Tests\Functional\AbstractNodeTest;
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
        $output = $this->simulateRendering('Test_OverriddenValueInPrototype.fusion');

        $this->assertTeaserConformsToBasicRendering($output);
        $this->assertMainContentConformsToBasicRendering($output);

        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-headline > div', 'Static Headline', true, $output);
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-text > div', 'Below, you\'ll see the most recent activity', true, $output);
        $this->assertSelectEquals('.sidebar', '[COMMIT WIDGET]', true, $output);
    }

    /**
     * @test
     */
    public function additionalProcessorInPrototype()
    {
        $output = $this->simulateRendering('Test_AdditionalProcessorInPrototype.fusion');

        $this->assertTeaserConformsToBasicRendering($output);
        $this->assertMainContentConformsToBasicRendering($output);

        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-headline > div > .processor-wrap', 'BEFOREStatic HeadlineAFTER', true, $output);
    }

    /**
     * @test
     */
    public function additionalProcessorInPrototype2()
    {
        $output = $this->simulateRendering('Test_AdditionalProcessorInPrototype2.fusion');

        $this->assertSelectEquals('.teaser > .neos-contentcollection > .neos-nodetypes-headline > div > header > h1', 'Welcome to this example', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .neos-nodetypes-headline > div > header > h1', 'Documentation', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .center > .neos-contentcollection > .neos-nodetypes-headline > div > header > h1', 'Development Process', true, $output);

        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-headline > div > header > .processor-wrap', 'BEFOREStatic HeadlineAFTER', true, $output);
    }

    /**
     * @test
     */
    public function replaceElementRenderingCompletelyInSidebar()
    {
        $output = $this->simulateRendering('Test_ReplaceElementRenderingCompletelyInSidebar.fusion');
        $this->assertTeaserConformsToBasicRendering($output);
        $this->assertMainContentConformsToBasicRendering($output);

        // header is now wrapped in h3
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-headline > header > h3', 'Last Commits', true, $output);
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-text > div', 'Below, you\'ll see the most recent activity', true, $output);
    }

    /**
     * @test
     */
    public function prototypeInheritance()
    {
        $output = $this->simulateRendering('Test_PrototypeInheritance.fusion');
        $this->assertSelectEquals('.teaser > .neos-contentcollection > .neos-nodetypes-headline > div > h1', 'Static Headline', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .neos-nodetypes-headline > div > h1', 'Static Headline', true, $output);

        // header is now wrapped in h3 (as set in the concrete template), AND is set to a static headline (as set in the abstract template)
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-headline > div > h1', 'Static Headline', true, $output);
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-text > div', 'Below, you\'ll see the most recent activity', true, $output);
    }

    /**
     * @test
     */
    public function replaceElementRenderingCompletelyBasedOnAdvancedCondition()
    {
        $output = $this->simulateRendering('Test_ReplaceElementRenderingCompletelyBasedOnAdvancedCondition.fusion');
        $this->assertTeaserConformsToBasicRendering($output);
        $this->assertSidebarConformsToBasicRendering($output);

        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .neos-nodetypes-headline > div > header', 'DOCS: Documentation', true, $output);
    }

    /**
     * @test
     */
    public function overriddenValueInNestedPrototype()
    {
        $output = $this->simulateRendering('Test_OverriddenValueInNestedPrototype.fusion');
        $this->assertTeaserConformsToBasicRendering($output);

        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .neos-nodetypes-headline > div > header', 'Static Headline', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .center > .neos-contentcollection > .neos-nodetypes-headline > div > header', 'Static Headline', true, $output);

        $this->assertSidebarConformsToBasicRendering($output);
    }

    /**
     * @test
     */
    public function overriddenValueInNestedPrototype2()
    {
        $output = $this->simulateRendering('Test_OverriddenValueInNestedPrototype2.fusion');
        $this->assertTeaserConformsToBasicRendering($output);

        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .neos-nodetypes-headline > div > header', 'Static Headline', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .center > .neos-contentcollection > .neos-nodetypes-headline > div > h1', 'Development Process', true, $output);

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
        $output = $this->simulateRendering('Test_AppendingClassesToContent.fusion');
        $this->assertSelectEquals('.teaser > .neos-contentcollection > .neos-nodetypes-headline.test h1', 'Welcome to this example', true, $output);
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-headline.test h1', 'Last Commits', true, $output);
    }

    /**
     * @test
     */
    public function menuWithNegativeEntryLevelIsRenderedAsExpected()
    {
        $output = $this->simulateRendering('Test_MenuNegativeEntryLevel.fusion');
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

        $this->assertSelectEquals('.teaser > .neos-contentcollection > .neos-nodetypes-headline > div > h1', 'Welcome to this example', true, $output);
        $this->assertSelectEquals('.teaser > .neos-contentcollection > .neos-nodetypes-text > div', 'This is our exemplary rendering test.', true, $output);
    }

    /**
     * Helper function for setting assertions
     * @param string $output
     */
    protected function assertMainContentConformsToBasicRendering($output)
    {
        $this->assertSelectEquals('.main > .neos-contentcollection > .neos-nodetypes-headline > div > h1', 'Do you love Flow?', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .neos-nodetypes-text > div', 'If you do, make sure to post your opinion about it on Twitter!', true, $output);

        $this->assertSelectEquals('.main', '[TWITTER WIDGET]', true, $output);

        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .neos-nodetypes-headline > div > h1', 'Documentation', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left > .neos-contentcollection > .neos-nodetypes-text > div', 'We\'re still improving our docs, but check them out nevertheless!', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .left', '[SLIDESHARE]', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .center > .neos-contentcollection > .neos-nodetypes-headline > div > h1', 'Development Process', true, $output);
        $this->assertSelectEquals('.main > .neos-contentcollection > .acme-demo-threecolumn > .center > .neos-contentcollection > .neos-nodetypes-text > div', 'We\'re spending lots of thought into our infrastructure, you can profit from that, too!', true, $output);
    }

    /**
     * Helper function for setting assertions
     * @param string $output
     */
    protected function assertSidebarConformsToBasicRendering($output)
    {
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-headline > div > h1', 'Last Commits', true, $output);
        $this->assertSelectEquals('.sidebar > .neos-contentcollection > .neos-nodetypes-text > div', 'Below, you\'ll see the most recent activity', true, $output);
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
            throw new \PHPUnit\Framework\Exception('Invalid count format');
        }
    }

    /**
     * Simulate the rendering
     *
     * @param string $additionalFusionFile
     * @param boolean $debugMode
     * @return string
     */
    protected function simulateRendering($additionalFusionFile = null, $debugMode = false)
    {
        $fusionRuntime = $this->createRuntimeWithFixtures($additionalFusionFile);
        $fusionRuntime->setEnableContentCache(false);
        if ($debugMode) {
            $fusionRuntime->injectSettings(array('debugMode' => true, 'rendering' => array('exceptionHandler' => \Neos\Fusion\Core\ExceptionHandlers\ThrowingHandler::class)));
        }
        $contentContext = $this->node->getContext();
        if (!$contentContext instanceof ContentContext) {
            $this->fail('Node context must be of type ContentContext');
        }
        $fusionRuntime->pushContextArray(array(
            'node' => $this->node,
            'documentNode' => $this->node,
            'site' => $contentContext->getCurrentSiteNode(),
            'fixturesDirectory' => __DIR__ . '/Fixtures'
        ));
        $output = $fusionRuntime->render('page1');
        $fusionRuntime->popContext();

        return $output;
    }

    /**
     * Create a Fusion runtime with the test base Fusion and an optional additional fixture
     *
     * @param string $additionalFusionFile
     * @return \Neos\Fusion\Core\Runtime
     */
    protected function createRuntimeWithFixtures($additionalFusionFile = null)
    {
        $fusionService = new FusionService();
        $fusionService->setSiteRootFusionPattern(__DIR__ . '/Fixtures/Base.fusion');

        if ($additionalFusionFile !== null) {
            $fusionService->setAppendFusionIncludes(array($additionalFusionFile));
        }

        $controllerContext = $this->buildMockControllerContext();

        $runtime = $fusionService->createRuntime($this->node->getParent(), $controllerContext);

        return $runtime;
    }

    /**
     * @return ControllerContext
     */
    protected function buildMockControllerContext()
    {
        $httpRequest = Request::create(new Uri('http://foo.bar/bazfoo'));
        $request = new ActionRequest($httpRequest);
        $response = new Response();
        /** @var Arguments $mockArguments */
        $mockArguments = $this->getMockBuilder(Arguments::class)->disableOriginalConstructor()->getMock();
        $uriBuilder = new UriBuilder();

        $controllerContext = new ControllerContext(
            $request,
            $response,
            $mockArguments,
            $uriBuilder,
            $this->createMock(FlashMessageContainer::class)
        );
        return $controllerContext;
    }
}
