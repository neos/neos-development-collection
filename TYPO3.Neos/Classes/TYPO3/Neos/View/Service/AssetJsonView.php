<?php
namespace TYPO3\Neos\View\Service;

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
use TYPO3\Flow\Mvc\View\JsonView;
use TYPO3\Media\Domain\Model\AssetInterface;

/**
 * A view specialised on a JSON representation of Assets.
 *
 * This view is used by the service controllers in TYPO3\Neos\Controller\Service\
 *
 * @Flow\Scope("prototype")
 */
class AssetJsonView extends JsonView {

	/**
	 * Configures rendering according to the set variable(s) and calls
	 * render on the parent.
	 *
	 * @return string
	 */
	public function render() {
		if (isset($this->variables['assets'])) {
			$this->setConfiguration(
				array(
					'assets' => array(
						'_descendAll' => array(
							'_only' => array('label', 'tags', 'identifier')
						)
					)
				)
			);
			$this->setVariablesToRender(array('assets'));
		} else {
			$this->setConfiguration(
				array(
					'asset' => array(
						'_only' => array('label', 'tags', 'identifier')
					)
				)
			);
			$this->setVariablesToRender(array('asset'));
		}

		return parent::render();
	}

}