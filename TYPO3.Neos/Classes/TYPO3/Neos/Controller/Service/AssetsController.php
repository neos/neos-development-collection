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
use TYPO3\Neos\View\Service\AssetJsonView;

/**
 * Rudimentary REST service for assets
 *
 * @Flow\Scope("singleton")
 */
class AssetsController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\TagRepository
	 */
	protected $tagRepository;

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
		'html' => 'TYPO3\Fluid\View\TemplateView',
		'json' => 'TYPO3\Neos\View\Service\AssetJsonView'
	);

	/**
	 * A list of IANA media types which are supported by this controller
	 *
	 * @var array
	 * @see http://www.iana.org/assignments/media-types/index.html
	 */
	protected $supportedMediaTypes = array(
		'text/html',
		'application/json'
	);

	/**
	 * Shows a list of assets
	 *
	 * @param string $searchTerm An optional search term used for filtering the list of assets
	 * @return string
	 */
	public function indexAction($searchTerm = '') {
		$assets = $this->assetRepository->findBySearchTermOrTags(
			$searchTerm,
			$this->tagRepository->findBySearchTerm($searchTerm)->toArray()
		);

		$this->view->assign('assets', $assets);
	}

	/**
	 * Shows a specific asset
	 *
	 * @param string $identifier Specifies the asset to look up
	 * @return string
	 */
	public function showAction($identifier) {
		$asset = $this->assetRepository->findByIdentifier($identifier);

		if ($asset === NULL) {
			$this->throwStatus(404);
		}

		$this->view->assign('asset', $asset);
	}

}
