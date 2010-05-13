<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Controller\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The TYPO3 Setup
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SetupController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @inject
	 * @var F3\TYPO3\Domain\Repository\Structure\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @inject
	 * @var F3\TYPO3\Domain\Repository\Structure\ContentNodeRepository
	 */
	protected $contentNodeRepository;

	/**
	 * @inject
	 * @var F3\TYPO3\Domain\Repository\Configuration\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @inject
	 * @var \F3\FLOW3\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @inject
	 * @var \F3\FLOW3\Security\AccountFactory
	 */
	protected $accountFactory;

	/**
	 * Sets up some data for playing around ...
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setupAction() {
		$this->siteRepository->removeAll();
		$this->contentNodeRepository->removeAll();
		$this->domainRepository->removeAll();
		$this->accountRepository->removeAll();

		$contentContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext');
		$contentService = $contentContext->getContentService();

		$site = $this->objectManager->create('F3\TYPO3\Domain\Model\Structure\Site');
		$site->setName('TYPO3 Phoenix Demo Site');
		$site->setNodeName('phoenix.demo.typo3.org');
		$site->setSiteResourcesPackageKey('PhoenixDemoTypo3Org');
		$this->siteRepository->add($site);

		$homepage = $contentService->createInside('homepage', 'F3\TYPO3\Domain\Model\Content\Page', $site);
		$homepage->setTitle('TYPO3 Phoenix');

		$xml = simplexml_load_file('http://search.twitter.com/search.atom?q=TYPO3+Phoenix');
		$tweet = (string)$xml->entry->title[0];
		
		$method = new \ReflectionMethod($this, 'setupAction');
		$phpCode = implode(chr(10), array_slice(explode(chr(10), str_replace("\n\t", "\n", file_get_contents(__FILE__))), $method->getStartLine() - 8, -2));
		$phpCode = \highlight_string("<?php $phpCode", TRUE);

    	$mainText1 = $contentService->createInside('text1', 'F3\TYPO3\Domain\Model\Content\Text', $homepage, 'main');
		$mainText1->setHeadline('TYPO3 Phoenix Hatched');
		$mainText1->setText('
			<p>The fact that you can read these lines means that TYPO3 Phoenix is able to render content.
				This page was automatically created on ' . date('F jS Y H:i (T)') . ' at ' . \gethostname() . ' by our demo setup controller.</p>
			<p>There is even <a href="homepage/anotherpage.html">another page</a> which demonstrates that support for sub pages is also implemented already.</p>
     	');

    	$mainText2 = $contentService->createInside('text2', 'F3\TYPO3\Domain\Model\Content\Text', $homepage, 'main');
		$mainText2->setHeadline('TypoScript');
		$mainText2->setText('
			<p>Here\'s the TypoScript template which renders this page:</p>
			<pre><code>' . file_get_contents('package://PhoenixDemoTypo3Org/Private/TypoScripts/homepage/Root.ts2') . '</code></pre>
		');

    	$mainText3 = $contentService->createInside('text3', 'F3\TYPO3\Domain\Model\Content\Text', $homepage, 'main');
		$mainText3->setHeadline('PHP');
		$mainText3->setText('
			<p>The content for this page was created by this PHP code:</p>
			<pre><code>' . $phpCode . '</code></pre>
		');

		$sideText = $contentService->createInside('samplecontent', 'F3\TYPO3\Domain\Model\Content\Text', $homepage, 'secondary');
		$sideText->setHeadline('Latest Tweet');
		$sideText->setText('
			<p>Here\'s the latest tweet about TYPO3 Phoenix at the time this page was created:</p>
			<p>' . $tweet . '</p>
     	');

		$anotherPage = $contentService->createInside('anotherpage', 'F3\TYPO3\Domain\Model\Content\Page', $homepage);
		$anotherPage->setTitle('Another Page');

    	$mainText1 = $contentService->createInside('text1', 'F3\TYPO3\Domain\Model\Content\Text', $anotherPage, 'main');
		$mainText1->setHeadline('Want More?');
		$mainText1->setText('
			<p>This is another page which exists for the solely purpose to demonstrate sub pages in TYPO3 Phoenix.</p>
     	');


		$account = $this->accountFactory->createAccountWithPassword('admin', 'password', array('Administrator'));
		$this->accountRepository->add($account);

		return 'Created some data for playing around.';
	}
}
?>