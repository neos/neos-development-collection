<?php
namespace TYPO3\Neos\Setup\Step;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.Neos".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
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
		$page1->setRenderingOption('header', 'Setup complete');

		$congratulations = $page1->createElement('congratulationsSection', 'TYPO3.Form:Section');
		$congratulations->setLabel('Congratulations');

		$success = $congratulations->createElement('success', 'TYPO3.Form:StaticText');
		$success->setProperty('text', 'You have successfully installed Neos! If you need help getting started, please refer to the Neos documentation.');
		$success->setProperty('elementClassAttribute', 'alert alert-success');

		$docs = $congratulations->createElement('docsLink', 'TYPO3.Setup:LinkElement');
		$docs->setLabel('Read the documentation');
		$docs->setProperty('href', 'http://docs.typo3.org/neos/');
		$docs->setProperty('target', '_blank');

		$frontend = $page1->createElement('frontendSection', 'TYPO3.Form:Section');
		$frontend->setLabel('View the site');

		$link = $frontend->createElement('link', 'TYPO3.Setup:LinkElement');
		$link->setLabel('Go to the frontend');
		$link->setProperty('href', '/');
		$link->setProperty('elementClassAttribute', 'btn btn-large btn-primary');

		$backend = $page1->createElement('backendSection', 'TYPO3.Form:Section');
		$backend->setLabel('Start editing');

		$backendLink = $backend->createElement('backendLink', 'TYPO3.Setup:LinkElement');
		$backendLink->setLabel('Go to the backend');
		$backendLink->setProperty('href', '/neos');
		$backendLink->setProperty('elementClassAttribute', 'btn btn-large btn-primary');
	}

}
