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
 */
class SetupController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @inject
	 * @var F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

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
	 * Action which displays a message that no site has yet been defined.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function noSiteAction() {
		$titles = array('Velkommen til TYPO3!', 'Willkommen zu TYPO3!', 'Welcome to TYPO3!',
			 '¡Bienvenido a TYPO3!', '¡Benvingut a TYPO3!', 'Laipni lūdzam TYPO3!', 'Bienvenue sur TYPO3!',
			 'Welkom op TYPO3!', 'Добро пожаловать в TYPO3!', 'ようこそTYPO3へ');
		$this->view->assign('title', $titles[rand(0, count($titles) - 1)]);
	}

	/**
	 * Displays the main setup screen with the opportunity to import a site provided
	 * by some package.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function indexAction() {
		$packagesWithSites = array();
		foreach ($this->packageManager->getActivePackages() as $package) {
			if (file_exists('resource://' . $package->getPackageKey() . '/Private/Content/Sites.xml')) {
				$packagesWithSites[$package->getPackageKey()] = $package->getPackageMetaData()->getTitle();
			}
		}
		$this->view->assign('packagesWithSites', $packagesWithSites);
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
		$this->importPackage($packageKey);
		$this->createAdministrator($identifier, $password, $person);
		$this->flashMessageContainer->flush();
		$this->redirect('index', 'Frontend\Page');
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
	 * Checks for the presence of Content.xml in the given package and imports
	 * it if found.
	 *
	 * @param string $packageKey
	 * @param string $identifier
	 * @param string $password
	 * @param \F3\Party\Domain\Model\Person $person
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function importPackage($packageKey) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			$this->flashMessageContainer->add('Error: Package "' . $packageKey . '" is not active.');
		} elseif (!file_exists('resource://' . $packageKey . '/Private/Content/Sites.xml')) {
			$this->flashMessageContainer->add('Error: No content found in package "' . $packageKey . '".');
		} else {
			$this->siteRepository->removeAll();
			$this->contentNodeRepository->removeAll();
			$this->domainRepository->removeAll();

			try {
				$this->importSitesFromPackage($packageKey);
				$this->flashMessageContainer->add('Imported website data from "' . $packageKey . '/Resources/Content/Sites.xml"');
			} catch (\Exception $e) {
				$this->flashMessageContainer->add('Error: During import an exception occured. ' . $e->getMessage());
			}
		}
	}

	/**
	 * Parses the Content.xml in the given package and imports the content into TYPO3.
	 *
	 * @param string $packageKey
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function importSitesFromPackage($packageKey) {
		$xml = new \SimpleXMLElement(file_get_contents('resource://' . $packageKey . '/Private/Content/Sites.xml'));
		foreach ($xml->structure as $site) {
			$siteNode = $this->objectManager->create((string)$site['type']);
			$siteNode->setNodeName((string)$site['nodename']);
			$siteNode->setName((string)$site->name);
			$siteNode->setState((integer)$site->state);
			$siteNode->setSiteResourcesPackageKey($packageKey);
			$this->parseSections($site->section, $siteNode);
			$this->siteRepository->add($siteNode);
		}
	}

	/**
	 * Iterates over the sections and adds the structure and content found to
	 * the $referencingNode.
	 *
	 * @param \SimpleXMLElement $sections
	 * @param \F3\TYPO3\Domain\Model\Structure\NodeInterface $referencingNode
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseSections(\SimpleXMLElement $sections, \F3\TYPO3\Domain\Model\Structure\NodeInterface $referencingNode) {
		foreach ($sections as $section) {
			$sectionName = (string)$section['name'];
			foreach ($section->structure as $structure) {
				$locale = $this->objectManager->create('F3\FLOW3\I18n\Locale', (string)$structure['locale']);
				$structureNode = $this->objectManager->create((string)$structure['type']);
				$structureNode->setNodeName((string)$structure['nodename']);
				$referencingNode->addChildNode($structureNode, $locale, $sectionName);
				if ($structure->content) {
					$this->createContentObject($structure->content, $structureNode);
				}
				$this->parseSections($structure->section, $structureNode);
			}
		}
	}

	/**
	 * Creates a content object attached to the $structureNode.
	 *
	 * @param \SimpleXMLElement $content
	 * @param \F3\TYPO3\Domain\Model\Structure\NodeInterface $structureNode
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createContentObject(\SimpleXMLElement $content, \F3\TYPO3\Domain\Model\Structure\NodeInterface $structureNode) {
		$contentNode = $this->objectManager->create((string)$content['type'], $this->objectManager->create('F3\FLOW3\I18n\Locale', (string)$content['locale']), $structureNode);

		foreach ($content->children() as $child) {
		 if (\F3\FLOW3\Reflection\ObjectAccess::isPropertySettable($contentNode, $child->getName())) {
			 \F3\FLOW3\Reflection\ObjectAccess::setProperty($contentNode, $child->getName(), (string)$child);
		 }
		}
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