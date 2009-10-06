<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Backend\Controller;

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
	 * @var array
	 */
	protected $supportedRequestTypes = array('F3\FLOW3\MVC\Web\Request');

	/**
	 * @inject
	 * @var F3\TYPO3\Domain\Repository\Structure\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @inject
	 * @var F3\TYPO3\Domain\Repository\Configuration\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @inject
	 * @var \F3\Party\Domain\Repository\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * Sets up some data for playing around ...
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setupAction() {
		foreach ($this->siteRepository->findAll() as $site) {
			$this->siteRepository->remove($site);
		}
		foreach ($this->domainRepository->findAll() as $domain) {
			$this->domainRepository->remove($domain);
		}

		foreach ($this->accountRepository->findAll() as $account) {
			$this->accountRepository->remove($account);
		}

		#-------------------------------------------------------------------------------------------

		$contentContext = $this->objectFactory->create('F3\TYPO3\Domain\Service\ContentContext');
		$contentService = $contentContext->getContentService();

		$site = $this->objectFactory->create('F3\TYPO3\Domain\Model\Structure\Site');
		$site->setName('TYPO3 5.0 Example Site');
		$this->siteRepository->add($site);

		$homePage = $contentService->createInside('F3\TYPO3\Domain\Model\Content\Page', $site);
		$homePage->setTitle('Homepage');

		$typoScriptTemplate = $this->objectFactory->create('F3\TYPO3\Domain\Model\Configuration\TypoScriptTemplate');
		$typoScriptTemplate->setSourceCode('
			namespace: default = F3\TYPO3\TypoScript

			page = Page

			page.title << 1.wrap("My ", "!")

		');
		$homePage->getContentNode()->addConfiguration($typoScriptTemplate);

		#-------------------------------------------------------------------------------------------

/*
		$site = $this->objectFactory->create('F3\TYPO3\Domain\Model\Structure\Site');
		$site->setName('Alternative Site');
		$this->siteRepository->add($site);

		$homePage = $contentService->createInside('F3\TYPO3\Domain\Model\Content\Page', $site);
		$homePage->setTitle('Alternative Homepage');

		$subPage = $contentService->createInside('F3\TYPO3\Domain\Model\Content\Page', $homePage);

		$domain = $this->objectFactory->create('F3\TYPO3\Domain\Model\Configuration\Domain');
		$domain->setHostPattern('localhost');
		$domain->setSite($site);
		$this->domainRepository->add($domain);
*/
		$account = $this->objectFactory->create('F3\Party\Domain\Model\Account');
		$credentials = md5(md5('password') . 'someSalt') . ',someSalt';

		$roles = array(
			$this->objectFactory->create('F3\FLOW3\Security\ACL\Role', 'Administrator'),
		);

		$account->setAccountIdentifier('admin');
		$account->setCredentialsSource($credentials);
		$account->setAuthenticationProviderName('TYPO3BEProvider');
		$account->setRoles($roles);

		$this->accountRepository->add($account);

		return 'Created some data for playing around.';
	}
}
?>