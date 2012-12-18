<?php
namespace TYPO3\Neos\Service\ExtDirect\V1\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * ExtDirect Controller for user preferences
 *
 * @Flow\Scope("singleton")
 */
class UserController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TYPO3\ExtJS\ExtDirect\View';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Party\Domain\Repository\PartyRepository
	 */
	protected $partyRepository;

	/**
	 * Select special error action
	 *
	 * @return void
	 */
	protected function initializeAction() {
		$this->errorMethodName = 'extErrorAction';
	}

	/**
	 * Update user preferences. The given array should contain key / value pairs of preference path => preference value
	 *
	 * @param array $preferences
	 * @return void
	 * @ExtDirect
	 */
	public function updatePreferencesAction(array $preferences) {
		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
		foreach ($preferences as $preferencePath => $value) {
			$user->getPreferences()->set($preferencePath, $value);
		}
		$this->partyRepository->update($user);
		$this->view->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));
		$this->view->assign('value', array('data' => '', 'success' => TRUE));
	}

	/**
	 * Get a user preference.
	 *
	 * @param string $preferencePath The preference key / path
	 * @return void
	 * @ExtDirect
	 */
	public function getPreferenceAction($preferencePath) {
		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
		$value = $user->getPreferences()->get($preferencePath);
		$this->view->setConfiguration(array('value' => array('data' => array('_descendAll' => array()))));
		$this->view->assign('value', array('data' => $value, 'success' => TRUE));
	}

	/**
	 * A preliminary error action for handling validation errors
	 * by assigning them to the ExtDirect View that takes care of
	 * converting them.
	 *
	 * @return void
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->arguments->getValidationResults());
	}


}

?>