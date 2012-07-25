<?php
namespace TYPO3\TYPO3\Setup\Step;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Setup".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3,
	TYPO3\Form\Core\Model\FormDefinition;

/**
 * @FLOW3\Scope("singleton")
 */
class FinalStep extends \TYPO3\Setup\Step\AbstractStep {

	/**
	 * Returns the form definitions for the step
	 *
	 * @param \TYPO3\Form\Core\Model\FormDefinition $formDefinition
	 * @return void
	 */
	protected function buildForm(\TYPO3\Form\Core\Model\FormDefinition $formDefinition) {
		$page1 = $formDefinition->createPage('page1');

		$title = $page1->createElement('connectionSection', 'TYPO3.Form:Section');
		$title->setLabel('Congratulations');

		$success = $title->createElement('success', 'TYPO3.Form:StaticText');
		$success->setProperty('text', 'You successfully completed the setup');
		$success->setProperty('class', 'alert alert-success');

		$link = $title->createElement('link', 'TYPO3.Setup:LinkElement');
		$link->setLabel('Go to the homepage');
		$link->setProperty('href', '/');
		$link->setProperty('class', 'btn btn-large btn-primary');

		$backendLink = $title->createElement('backendLink', 'TYPO3.Setup:LinkElement');
		$backendLink->setLabel('Go to the backend');
		$backendLink->setProperty('href', '/typo3');
		$backendLink->setProperty('class', 'btn btn-large');
	}

}
?>