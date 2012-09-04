<?php
namespace TYPO3\TYPO3\Service\ExtDirect\V1\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * ExtDirect Controller for user preferences
 *
 * @FLOW3\Scope("singleton")
 */
class UserController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TYPO3\ExtJS\ExtDirect\View';

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @FLOW3\Inject
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
		foreach ($preferences as $preferencePath => $value) {
			$this->securityContext->getParty()->getPreferences()->set($preferencePath, $value);
		}
		$this->partyRepository->update($this->securityContext->getParty());
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
		$value = $this->securityContext->getParty()->getPreferences()->get($preferencePath);
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