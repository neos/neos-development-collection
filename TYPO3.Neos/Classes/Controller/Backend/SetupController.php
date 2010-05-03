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

		$homePage = $contentService->createInside('homepage', 'F3\TYPO3\Domain\Model\Content\Page', $site);
		$homePage->setTitle('Welcome to TYPO3 Phoenix!');

		$account = $this->accountFactory->createAccountWithPassword('admin', 'password', array('Administrator'));
		$this->accountRepository->add($account);

		return 'Created some data for playing around.';
	}
}
?>