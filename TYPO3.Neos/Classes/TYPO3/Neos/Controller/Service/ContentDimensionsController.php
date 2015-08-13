<?php
namespace TYPO3\Neos\Controller\Service;

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
use TYPO3\Flow\Mvc\View\JsonView;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

/**
 * REST service controller for managing content dimensions
 */
class ContentDimensionsController extends ActionController {

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
		'html' => 'TYPO3\Fluid\View\TemplateView',
		'json' => 'TYPO3\Flow\Mvc\View\JsonView'
	);

	/**
	 * @var array
	 */
	protected $supportedMediaTypes = array(
		'text/html',
		'application/json'
	);

	/**
	 * @var ContentDimensionPresetSourceInterface
	 * @Flow\Inject
	 */
	protected $contentDimensionPresetSource;

	/**
	 * Returns the full content dimension presets as JSON object; see
	 * ContentDimensionPresetSourceInterface::getAllPresets() for a format
	 * description.
	 *
	 * @return void
	 */
	public function indexAction() {
		if ($this->view instanceof JsonView) {
			$this->view->assign('value', $this->contentDimensionPresetSource->getAllPresets());
		} else {
			$this->view->assign('contentDimensionsPresets', $this->contentDimensionPresetSource->getAllPresets());
		}
	}
}