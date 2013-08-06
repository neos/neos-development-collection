<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * ContextFactory Interface
 *
 */
interface ContextFactoryInterface {

	/**
	 * Create the context from the given properties. If a context with those properties was already
	 * created before then the existing one is returned.
	 *
	 * The context properties to give depend on the implementation of the context object, for the
	 * TYPO3\TYPO3CR\Domain\Service\Context it should look like this:
	 *
	 * array(
	 *        'workspaceName' => 'live',
	 *        'currentDateTime' => new \TYPO3\Flow\Utility\Now(),
	 *        'locale' => new \TYPO3\Flow\I18n\Locale('mul_ZZ'),
	 *        'invisibleContentShown' => FALSE,
	 *        'removedContentShown' => FALSE,
	 *        'inaccessibleContentShown' => FALSE
	 * )
	 *
	 * @param array $contextConfiguration
	 * @return \TYPO3\TYPO3CR\Domain\Service\ContextInterface
	 * @api
	 */
	public function create(array $contextConfiguration);

}

?>