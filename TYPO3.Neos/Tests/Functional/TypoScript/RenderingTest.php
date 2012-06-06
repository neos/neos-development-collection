<?php
namespace TYPO3\TYPO3\Tests\Functional\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Functional test case which tests the rendering
 *
 * @group large
 */
class RenderingTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var boolean
	 */
	protected $testableHttpEnabled = TRUE;

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $node;

	public function setUp() {
		parent::setUp();
		$nodeRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeRepository');
		\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($nodeRepository, 'context', new \TYPO3\TYPO3\Domain\Service\ContentContext('live'), TRUE);
		$siteImportService = $this->objectManager->get('TYPO3\TYPO3\Domain\Service\SiteImportService');
		$siteImportService->importSitesFromFile(__DIR__ . '/Fixtures/NodeStructure.xml');
		$this->persistenceManager->persistAll();

		$propertyMapper = $this->objectManager->get('TYPO3\FLOW3\Property\PropertyMapper');
		$this->node = $propertyMapper->convert('/sites/example/home', 'TYPO3\TYPO3CR\Domain\Model\Node');
		$this->assertFalse($propertyMapper->getMessages()->hasErrors());
	}

	/**
	 * @test
	 */
	public function basicRenderingWorks() {
		$output = $this->simulateRendering();

		$this->assertTeaserConformsToBasicRendering($output);
		$this->assertMainContentConformsToBasicRendering($output);
		$this->assertSidebarConformsToBasicRendering($output);
	}

	/**
	 * @test
	 */
	public function overriddenValueInPrototype() {
		$output = $this->simulateRendering('Test_OverriddenValueInPrototype.ts2');

		$this->assertTeaserConformsToBasicRendering($output);
		$this->assertMainContentConformsToBasicRendering($output);

		$this->assertSelectEquals('.sidebar > .typo3-typo3-textwithheadline > h1', 'Static Headline', TRUE, $output);
		$this->assertSelectEquals('.sidebar > .typo3-typo3-textwithheadline > div', 'Below, you\'ll see the most recent activity', TRUE, $output);
		$this->assertSelectEquals('.sidebar', '[COMMIT WIDGET]', TRUE, $output);
	}

	/**
	 * @test
	 */
	public function additionalProcessorInPrototype() {
		$output = $this->simulateRendering('Test_AdditionalProcessorInPrototype.ts2');
		$this->assertTeaserConformsToBasicRendering($output);
		$this->assertMainContentConformsToBasicRendering($output);
		$this->assertSidebarConformsToBasicRendering($output);

		$this->assertSelectEquals('.sidebar > .typo3-typo3-textwithheadline > h1', 'BEFORELast CommitsAFTER', TRUE, $output);
	}

	/**
	 * @test
	 */
	public function additionalProcessorInPrototype2() {
		$output = $this->simulateRendering('Test_AdditionalProcessorInPrototype2.ts2');
		$this->assertTeaserConformsToBasicRendering($output);
		$this->assertMainContentConformsToBasicRendering($output);
		$this->assertSidebarConformsToBasicRendering($output);

		$this->assertSelectEquals('.teaser > .typo3-typo3-textwithheadline > h1', '-b-Welcome to this example-a-', TRUE, $output);
		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .left > .typo3-typo3-textwithheadline > h1', '-b-Documentation-a-', TRUE, $output);
		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .center > .typo3-typo3-textwithheadline > h1', '-b-Development Process-a-', TRUE, $output);

		$this->assertSelectEquals('.sidebar > .typo3-typo3-textwithheadline > h1', '-b-BEFORELast CommitsAFTER-a-', TRUE, $output);
	}

	/**
	 * @test
	 */
	public function replaceElementRenderingCompletelyInSidebar() {
		$output = $this->simulateRendering('Test_ReplaceElementRenderingCompletelyInSidebar.ts2');
		$this->assertTeaserConformsToBasicRendering($output);
		$this->assertMainContentConformsToBasicRendering($output);

			// h2 is now a h3
		$this->assertSelectEquals('.sidebar > .typo3-typo3-textwithheadline > h3', 'Last Commits', TRUE, $output);
		$this->assertSelectEquals('.sidebar > .typo3-typo3-textwithheadline > div', 'Below, you\'ll see the most recent activity', TRUE, $output);
	}

	/**
	 * @test
	 */
	public function replaceElementRenderingCompletelyBasedOnAdvancedCondition() {
		$output = $this->simulateRendering('Test_ReplaceElementRenderingCompletelyBasedOnAdvancedCondition.ts2');
		$this->assertTeaserConformsToBasicRendering($output);
		$this->assertMainContentConformsToBasicRendering($output);
		$this->assertSidebarConformsToBasicRendering($output);

		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .left > .typo3-typo3-textwithheadline > h1', 'DOCS: Documentation', TRUE, $output);
	}

	/**
	 * @test
	 */
	public function overriddenValueInNestedPrototype() {
		$output = $this->simulateRendering('Test_OverriddenValueInNestedPrototype.ts2');
		$this->assertTeaserConformsToBasicRendering($output);

		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .left > .typo3-typo3-textwithheadline > h1', 'Static Headline', TRUE, $output);
		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .center > .typo3-typo3-textwithheadline > h1', 'Static Headline', TRUE, $output);

		$this->assertSidebarConformsToBasicRendering($output);
	}

	/**
	 * @test
	 */
	public function overriddenValueInNestedPrototype2() {
		$output = $this->simulateRendering('Test_OverriddenValueInNestedPrototype2.ts2');
		$this->assertTeaserConformsToBasicRendering($output);

		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .left > .typo3-typo3-textwithheadline > h1', 'Static Headline', TRUE, $output);
		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .center > .typo3-typo3-textwithheadline > h1', 'Development Process', TRUE, $output);

		$this->assertSidebarConformsToBasicRendering($output);
	}


	protected function assertTeaserConformsToBasicRendering($output) {
		$this->assertContains('TYPO3 Phoenix is based on FLOW3, a powerful PHP application framework licensed under the GNU/LGPL.', $output);
		$this->assertSelectEquals('h1', 'Home', TRUE, $output);
		$this->assertSelectEquals('.teaser > .typo3-typo3-textwithheadline > h1', 'Welcome to this example', TRUE, $output);
		$this->assertSelectEquals('.teaser > .typo3-typo3-textwithheadline > div', 'This is our exemplary rendering test.', TRUE, $output);
	}

	protected function assertMainContentConformsToBasicRendering($output) {
		$this->assertSelectEquals('.main > .typo3-typo3-textwithheadline > h1', 'Do you love FLOW3?', TRUE, $output);
		$this->assertSelectEquals('.main > .typo3-typo3-textwithheadline > div', 'If you do, make sure to post your opinion about it on Twitter!', TRUE, $output);

		$this->assertSelectEquals('.main', '[TWITTER WIDGET]', TRUE, $output);

		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .left > .typo3-typo3-textwithheadline > h1', 'Documentation', TRUE, $output);
		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .left > .typo3-typo3-textwithheadline > div', 'We\'re still improving our docs, but check them out nevertheless!', TRUE, $output);
		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .left', '[SLIDESHARE]', TRUE, $output);
		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .center > .typo3-typo3-textwithheadline > h1', 'Development Process', TRUE, $output);
		$this->assertSelectEquals('.main > .typo3-typo3-threecolumn > .center > .typo3-typo3-textwithheadline > div', 'We\'re spending lots of thought into our infrastructure, you can profit from that, too!', TRUE, $output);
	}

	protected function assertSidebarConformsToBasicRendering($output) {
		$this->assertSelectEquals('.sidebar > .typo3-typo3-textwithheadline > h1', 'Last Commits', TRUE, $output);
		$this->assertSelectEquals('.sidebar > .typo3-typo3-textwithheadline > div', 'Below, you\'ll see the most recent activity', TRUE, $output);
		$this->assertSelectEquals('.sidebar', '[COMMIT WIDGET]', TRUE, $output);
	}

	public static function assertSelectEquals($selector, $content, $count, $actual, $message = '', $isHtml = TRUE) {
		if ($message === '') {
			 $message = $selector . ' did not match.';
		}
		parent::assertSelectEquals($selector, $content, $count, $actual, $message, $isHtml);
	}

	protected function simulateRendering($additionalTypoScriptFile = NULL) {
		$typoScriptRuntime = $this->parseTypoScript($additionalTypoScriptFile);
		$typoScriptRuntime->pushContext($this->node);
		$output = $typoScriptRuntime->render('page1');
		$typoScriptRuntime->popContext();

		return $output;
	}

	protected function parseTypoScript($additionalTypoScriptFile = NULL) {
		$typoScript = file_get_contents(__DIR__ . '/Fixtures/PredefinedTypoScript.ts2');
		$typoScript .= chr(10) . chr(10) . file_get_contents(__DIR__ . '/Fixtures/BaseTypoScript.ts2');

		if ($additionalTypoScriptFile != NULL) {
			$typoScript .= chr(10) . chr(10) . file_get_contents(__DIR__ . '/Fixtures/' . $additionalTypoScriptFile);
		}
		$typoScript = str_replace('FIXTURE_DIRECTORY', __DIR__ . '/Fixtures/', $typoScript);

		$parser = new \TYPO3\TypoScript\Core\Parser();
		$typoScriptConfiguration = $parser->parse($typoScript);


		$httpRequest = \TYPO3\FLOW3\Http\Request::create(new \TYPO3\FLOW3\Http\Uri('http://foo.bar/bazfoo'));
		$request = $httpRequest->createActionRequest();
		$response = new \TYPO3\FLOW3\Http\Response();


		$controllerContext = new \TYPO3\FLOW3\Mvc\Controller\ControllerContext(
			$request,
			$response,
			$this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array(), array(), '', FALSE),
			$this->getMock('TYPO3\FLOW3\Mvc\Routing\UriBuilder'),
			$this->getMock('TYPO3\FLOW3\Mvc\FlashMessageContainer')
		);
		return new \TYPO3\TypoScript\Core\Runtime($typoScriptConfiguration, $controllerContext);
	}
}
?>