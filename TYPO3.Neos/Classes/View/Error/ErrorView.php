<?php
namespace TYPO3\TYPO3\View\Error;

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

/**
 * A TYPO3 error view with a static template
 *
 * @FLOW3\Scope("prototype")
 */
class ErrorView extends \TYPO3\FLOW3\Mvc\View\NotFoundView {

	/**
	 * Variable names and markers for substitution in static template
	 *
	 * @var array
	 */
	protected $variablesMarker = array(
		'errorTitle' => 'ERROR_TITLE',
		'errorSubtitle' => 'ERROR_SUBTITLE',
		'errorDescription' => 'ERROR_DESCRIPTION'
	);

	/**
	 * Get the template path and filename for the page not found template
	 *
	 * @return string path and filename of the not-found-template
	 */
	protected function getTemplatePathAndFilename() {
		return 'resource://TYPO3.TYPO3/Private/Templates/Frontend/Error/NotFound.html';
	}
}
?>