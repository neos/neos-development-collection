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
use TYPO3\Flow\Mvc\Controller\ActionController;

/**
 * Abstract Service Controller
 */
abstract class AbstractServiceController extends ActionController {

	/**
	 * A preliminary error action for handling validation errors
	 *
	 * @return void
	 */
	public function errorAction() {
		if ($this->arguments->getValidationResults()->hasErrors()) {
			$errors = array();
			foreach ($this->arguments->getValidationResults()->getFlattenedErrors() as $propertyName => $propertyErrors) {
				foreach ($propertyErrors as $propertyError) {
					/** @var \TYPO3\Flow\Error\Error $propertyError */
					$error = array(
						'severity' => $propertyError->getSeverity(),
						'message' => $propertyError->render()
					);
					if ($propertyError->getCode()) {
						$error['code'] = $propertyError->getCode();
					}
					if ($propertyError->getTitle()) {
						$error['title'] = $propertyError->getTitle();
					}
					$errors[$propertyName][] = $error;
				}
			}
			$this->throwStatus(409, NULL, json_encode($errors));
		}
		$this->throwStatus(400);
	}

}