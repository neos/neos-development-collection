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
 * The TYPO3 Backend controller
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class BackendController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('F3\FLOW3\MVC\Web\Request');

	/**
	 * @inject
	 * @var F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @inject
	 * @var F3\FLOW3\Package\ManagerInterface
	 */
	protected $packageManager;

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
	 * Default action of the backend controller.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function indexAction() {
		$this->view->assign('TYPO3Version', $this->packageManager->getPackage('TYPO3')->getPackageMetaData()->getVersion());
		$this->view->assign('installationHost', gethostname());
		$this->view->assign('sections', array('frontend' => 'Frontend', 'content' => 'Content', 'layout' => 'Layout', 'report' => 'Report', 'administration' => 'Administration'));
	}

	/**
	 * Sets up some data for playing around ...
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setupAction() {
		$contentContext = $this->objectFactory->create('F3\TYPO3\Domain\Service\ContentContext');
		$contentService = $contentContext->getContentService();

		$site = $this->objectFactory->create('F3\TYPO3\Domain\Model\Structure\Site');
		$site->setName('FLOW3');
		$this->siteRepository->add($site);

		$homePage = $contentService->createInside('F3\TYPO3\Domain\Model\Content\Page', $site);
		$homePage->setTitle('Homepage');

#		$domain = $this->objectFactory->create('F3\TYPO3\Domain\Model\Configuration\Domain');
#		$domain->setHostPattern('localhost');
#		$domain->setSite($site);
#		$this->domainRepository->add($domain);

		$homePage = $contentService->createInside('F3\TYPO3\Domain\Model\Content\Page', $site);
		$subPage = $contentService->createInside('F3\TYPO3\Domain\Model\Content\Page', $homePage);

		$homePage->setTitle('Homepage');

		$site = $this->objectFactory->create('F3\TYPO3\Domain\Model\Structure\Site');
		$site->setName('TYPO3');
		$this->siteRepository->add($site);

		$domain = $this->objectFactory->create('F3\TYPO3\Domain\Model\Configuration\Domain');
		$domain->setHostPattern('localhost');
		$domain->setSite($site);
		$this->domainRepository->add($domain);

		return 'Created some data for playing around.';
	}
}
?>