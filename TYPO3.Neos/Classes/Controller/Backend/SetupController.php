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
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope singleton
 */
class SetupController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @inject
	 * @var F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @inject
	 * @var \F3\FLOW3\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @inject
	 * @var \F3\Party\Domain\Repository\PersonRepository
	 */
	protected $personRepository;

	/**
	 * @inject
	 * @var \F3\FLOW3\Security\AccountFactory
	 */
	protected $accountFactory;

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Service\SiteImportService
	 */
	protected $siteImportService;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @inject
	 * @var F3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * Displays the main setup screen with the opportunity to import a site provided
	 * by some package.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function indexAction() {
		$contentContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext', 'live');
		$homepageNode  = $contentContext->getWorkspace()->getRootNode()->getNode('/sites/phoenixdemotypo3org/homepage');

		if($homepageNode !== NULL) {
			$this->forward('alreadyExecuted');
		}

		if ($this->siteRepository->countAll() === 1) {
			$this->forward('reimportContent');
		}

		foreach ($this->packageManager->getActivePackages() as $package) {
			if (file_exists('resource://' . $package->getPackageKey() . '/Private/Content/Sites.xml')) {
				$packagesWithSites[$package->getPackageKey()] = $package->getPackageMetaData()->getTitle();
			}
		}
		$packagesWithSites['0'] = '';
		$this->view->assign('packagesWithSites', $packagesWithSites);

		$titles = array('Velkommen til TYPO3!', 'Willkommen zu TYPO3!', 'Welcome to TYPO3!',
			 '¡Bienvenido a TYPO3!', '¡Benvingut a TYPO3!', 'Laipni lūdzam TYPO3!', 'Bienvenue sur TYPO3!',
			 'Welkom op TYPO3!', 'Добро пожаловать в TYPO3!', 'ようこそTYPO3へ');
		$this->view->assign('title', $titles[rand(0, count($titles) - 1)]);
	}

	/**
	 * Initializes the importAndCreateAdministratorAction
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeImportAndCreateAdministratorAction() {
		$this->arguments['person']->getPropertyMappingConfiguration()->allowCreationForSubProperty('name');
	}

	/**
	 * Imports content and creates user, then redirects to frontend.
	 *
	 * @param string $packageKey Specifies the package which contains the site to be imported
	 * @param string $identifier Identifier of the account to be created
	 * @param string $password The clear text password of the new account
	 * @param \F3\Party\Domain\Model\Person $person Person containing first and last name. Will be owner of the new account.
	 * @return void
	 * @validate $identifier Label, NotEmpty
	 * @validate $password NotEmpty
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function importAndCreateAdministratorAction($packageKey, $identifier, $password, \F3\Party\Domain\Model\Person $person) {
		if($this->siteRepository->countAll() === 1) {
			$this->forward('alreadyExecuted');
		}
		if ($packageKey !== '0') {
			try {
				$this->siteImportService->importPackage($packageKey);
				$this->flashMessageContainer->add('Imported website data from "' . $packageKey . '/Resources/Content/Sites.xml"');
			} catch (\Exception $exception) {
				$this->flashMessageContainer->add($exception->getMessage());
				$this->redirect('index');
			}
		}

		$this->accountRepository->removeAll();
		$this->personRepository->removeAll();
		$this->createAdministrator($identifier, $password, $person);
		$this->flashMessageContainer->flush();
		$this->redirect('show', 'Node', 'TYPO3\Service\Rest\V1');
	}

	/**
	 * Action which displays a message that the setup has been executed already
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function alreadyExecutedAction() {
	}

	/**
	 * Reimports the content of the live workspace for the site which was originally
	 * imported.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reimportContentAction() {
		$site = $this->siteRepository->findFirst();
		$packageKey = $site->getSiteResourcesPackageKey();

		$this->siteImportService->updateFromPackage($packageKey);

		$this->flashMessageContainer->flush();
		$this->redirect('show', 'Node');
	}

	/**
	 * Create a user with the Administrator role.
	 *
	 * @param string $identifier Identifier of the account to be created
	 * @param string $password The clear text password of the new account
	 * @param \F3\Party\Domain\Model\Person $person Person containing first and last name. Will be owner of the new account.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function createAdministrator($identifier, $password, \F3\Party\Domain\Model\Person $person) {
		$account = $this->accountFactory->createAccountWithPassword($identifier, $password, array('Administrator'));
		$account->setParty($person);
		$this->accountRepository->add($account);
	}

	/**
	 * A template method for displaying custom error flash messages, or to
	 * display no flash message at all on errors. Override this to customize
	 * the flash message in your action controller.
	 *
	 * @return string|boolean The flash message or FALSE if no flash message should be set
	 * @api
	 */
	protected function getErrorFlashMessage() {
		return '';
	}

}
?>