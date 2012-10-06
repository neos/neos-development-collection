<?php
namespace TYPO3\TYPO3\View\Error;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A page not found error view
 *
 */
class PageNotFoundView extends \TYPO3\TYPO3\View\Error\ErrorView {

	/**
	 * Pre-filled variables for page not found labels
	 *
	 * @var array
	 */
	protected $variables = array(
		'errorTitle' => 'Ooops, it looks like we\'ve made a mistake, something has gone wrong with this page.',
		'errorSubtitle' => 'Technical reason:<br/>404 - Page not found.',
		'errorDescription' => 'It seems something has gone wrong, the page you where looking for either does not exist or there has been an error in the URL. There is a good chance that this is not something you\'ve done wrong, but an error that we\'re not yet aware of.'
	);

}
?>