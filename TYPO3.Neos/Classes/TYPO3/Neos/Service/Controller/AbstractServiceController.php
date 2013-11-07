<?php
namespace TYPO3\Neos\Service\Controller;

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

/**
 * Abstract Service Controller
 */
abstract class AbstractServiceController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * A preliminary error action for handling validation errors
	 *
	 * @return void
	 */
	public function errorAction() {
		if ($this->arguments->getValidationResults()->hasErrors()) {
			$this->throwStatus(409, NULL, json_encode($this->arguments->getValidationResults()->getFlattenedErrors()));
		}
		$this->throwStatus(400);
	}

}