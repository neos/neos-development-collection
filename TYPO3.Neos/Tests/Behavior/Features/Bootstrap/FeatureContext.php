<?php

use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\TableNode,
	Behat\MinkExtension\Context\MinkContext;
use TYPO3\Flow\Utility\Arrays;
use PHPUnit_Framework_Assert as Assert;

require_once(__DIR__ . '/../../../../../Flowpack.Behat/Tests/Behat/FlowContext.php');

/**
 * Features context
 */
class FeatureContext extends MinkContext {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Initializes the context
	 *
	 * @param array $parameters Context parameters (configured through behat.yml)
	 */
	public function __construct(array $parameters) {
		$this->useContext('flow', new \Flowpack\Behat\Tests\Behat\FlowContext($parameters));
		$this->objectManager = $this->getSubcontext('flow')->getObjectManager();
	}

	/**
	 * @Given /^I am not authenticated$/
	 */
	public function iAmNotAuthenticated() {
		// Do nothing, every scenario has a new session
	}

	/**
	 * @Then /^I should see a login form$/
	 */
	public function iShouldSeeALoginForm() {
		$this->assertSession()->fieldExists('Username');
		$this->assertSession()->fieldExists('Password');
	}

	/**
	 * @Given /^the following users exist:$/
	 */
	public function theFollowingUsersExist(TableNode $table) {
		$rows = $table->getHash();
		/** @var \TYPO3\Neos\Domain\Factory\UserFactory $userFactory */
		$userFactory = $this->objectManager->get('TYPO3\Neos\Domain\Factory\UserFactory');
		/** @var \TYPO3\Party\Domain\Repository\PartyRepository $partyRepository */
		$partyRepository = $this->objectManager->get('TYPO3\Party\Domain\Repository\PartyRepository');
		/** @var \TYPO3\Flow\Security\AccountRepository $accountRepository */
		$accountRepository = $this->objectManager->get('TYPO3\Flow\Security\AccountRepository');
		foreach ($rows as $row) {
			$roleIdentifiers = array_map(function($role) {
				return 'TYPO3.Neos:' . $role;
			}, Arrays::trimExplode(',', $row['roles']));
			$user = $userFactory->create($row['username'], $row['password'], $row['firstname'], $row['lastname'], $roleIdentifiers);

			$partyRepository->add($user);
			$accounts = $user->getAccounts();
			foreach ($accounts as $account) {
				$accountRepository->add($account);
			}
		}
		$this->getSubcontext('flow')->persistAll();
	}

	/**
	 * @Given /^I am authenticated with "([^"]*)" and "([^"]*)" for the backend$/
	 */
	public function iAmAuthenticatedWithAndForTheBackend($username, $password) {
		$this->visit('/neos/login');
		$this->fillField('Username', $username);
		$this->fillField('Password', $password);
		$this->pressButton('Login');
	}

	/**
	 * @Then /^I should be on the "([^"]*)" page$/
	 */
	public function iShouldBeOnThePage($page) {
		switch ($page) {
			case 'Login':
				$this->assertSession()->addressEquals('/neos/login');
				break;
			default:
				throw new PendingException();
		}
	}

	/**
	 * @Then /^I should be in the "([^"]*)" module$/
	 */
	public function iShouldBeInTheModule($moduleName) {
		switch ($moduleName) {
			case 'Content':
				$this->assertSession()->elementTextContains('css', '#neos-top-bar .neos-active a', 'Content');
				break;
			default:
				throw new PendingException();
		}
	}

	/**
	 * @When /^I follow "([^"]*)" in the main menu$/
	 */
	public function iFollowInTheMainMenu($link) {
		$this->assertElementOnPage('ul.nav');
		$this->getSession()->getPage()->find('css', 'ul.nav')->findLink($link)->click();
	}

	/**
	 * @Given /^I should be logged in as "([^"]*)"$/
	 */
	public function iShouldBeLoggedInAs($name) {
		$this->assertSession()->elementTextContains('css', '#neos-application .neos-button-logout a', $name);
	}

	/**
	 * @Then /^I should not be logged in$/
	 */
	public function iShouldNotBeLoggedIn() {
		$this->assertSession()->elementNotExists('css', '#neos-application .neos-button-logout');
	}

	/**
	 * @Given /^I should see the page title "([^"]*)"$/
	 */
	public function iShouldSeeThePageTitle($title) {
		$this->assertSession()->elementTextContains('css', 'title', $title);
	}

	/**
	 * @Then /^I should not see the top bar$/
	 */
	public function iShouldNotSeeTheInspectorPanel() {
		$this->assertElementOnPage('.neos-previewmode #neos-top-bar');
	}

	/**
	 * @Given /^the button "([^"]*)" should be active$/
	 */
	public function theButtonShouldBeActive($buttonName) {
		$button = $this->getSession()->getPage()->findButton($buttonName);
		if ($button === NULL) {
			throw new \Behat\Mink\Exception\ElementNotFoundException($this->getSession(), 'button', 'id|name|label|value', $buttonName);
		}

		Assert::assertTrue($button->hasClass('pressed'), 'Button should be pressed');
	}

	/**
	 * @Given /^I imported the site "([^"]*)"$/
	 */
	public function iImportedTheSite($packageKey) {
		/** @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository $nodeRepository */
		$nodeRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeRepository');
		$contentContext = new \TYPO3\Neos\Domain\Service\ContentContext('live');
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($nodeRepository, 'context', $contentContext, TRUE);

		/** @var \TYPO3\Neos\Domain\Service\SiteImportService $siteImportService */
		$siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
		$siteImportService->importFromPackage($packageKey);

		$this->getSubcontext('flow')->persistAll();
	}

	/**
	 * @When /^I go to the "([^"]*)" module$/
	 */
	public function iGoToTheModule($module) {
		switch ($module) {
			case 'Administration / Site Management':
				$this->visit('/neos/administration/sites');
				break;
			default:
				throw new PendingException();
		}
	}

	/**
	 * @BeforeScenario @fixtures
	 */
	public function removeTestSitePackages() {
		$directories = glob(FLOW_PATH_PACKAGES . 'Sites/Test.*');
		if (is_array($directories)) {
			foreach ($directories as $directory) {
				\TYPO3\Flow\Utility\Files::removeDirectoryRecursively($directory);
			}
		}
	}

	/**
	 * @Then /^I should see the following sites in a table:$/
	 */
	public function iShouldSeeTheFollowingSitesInATable(TableNode $table) {
		$sites = $table->getHash();

		$tableLocator = '.neos-module-container table.table';
		$sitesTable = $this->assertSession()->elementExists('css', $tableLocator);

		$siteRows = $sitesTable->findAll('css', 'tbody tr');
		$actualSites = array_map(function($row) {
			return array(
				'name' => $row->find('css', 'td:first-child')->getText()
			);
		}, $siteRows);

		$sortByName = function($a, $b) {
			return strcmp($a['name'], $b['name']);
		};
		usort($sites, $sortByName);
		usort($actualSites, $sortByName);

		Assert::assertEquals($sites, $actualSites);
	}

	/**
     * @Given /^I follow "([^"]*)" for site "([^"]*)"$/
     */
    public function iFollowForSite($link, $siteName) {
		$rowLocator = sprintf("//table[@class='table']//tr[td/text()='%s']", $siteName);
		$siteRow = $this->assertSession()->elementExists('xpath', $rowLocator);
		$siteRow->findLink($link)->click();
    }

	/**
	 * @param string $path
	 * @return string
	 */
	public function locatePath($path) {
		return parent::locatePath($this->getSubcontext('flow')->resolvePath($path));
	}

}
?>