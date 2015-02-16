<?php
namespace TYPO3\TYPO3CR\Tests\Behavior\Features\Bootstrap;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A trait with shared step definitions for common use by other contexts
 *
 * Note that this trait requires that the TYPO3CR authorization service must be available in $this->nodeAuthorizationService;
 *
 * Note: This trait expects the IsolatedBehatStepsTrait and the NodeOperationsTrait to be available!
 */
trait NodeAuthorizationTrait {

	/**
	 * @Flow\Inject
	 * @var TYPO3\TYPO3CR\Service\AuthorizationService
	 */
	protected $nodeAuthorizationService;
}
