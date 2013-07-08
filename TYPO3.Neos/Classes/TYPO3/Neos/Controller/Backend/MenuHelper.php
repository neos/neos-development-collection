<?php
namespace TYPO3\Neos\Controller\Backend;

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
 * A helper class for menu generation in backend controllers / view helpers
 *
 * @Flow\Scope("singleton")
 */
class MenuHelper {

	/**
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 * @Flow\Inject
	 */
	protected $siteRepository;

	/**
	 * Build a list of sites
	 *
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 * @return array
	 */
	public function buildSiteList(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		$requestUriHost = $controllerContext->getRequest()->getHttpRequest()->getUri()->getHost();
		$domainsFound = FALSE;
		$sites = array();
		foreach ($this->siteRepository->findAll() as $site) {
			$uri = NULL;
			/** @var $site \TYPO3\Neos\Domain\Model\Site */
			if ($site->hasActiveDomains()) {
				$uri = $controllerContext->getUriBuilder()
					->reset()
					->uriFor('index', array(), 'Backend\Backend', 'TYPO3.Neos');
				$uri = 'http://' . $site->getFirstActiveDomain()->getHostPattern() . '/' . $uri;
				$domainsFound = TRUE;
			}

			$sites[] = array(
				'name' => $site->getName(),
				'nodeName' => $site->getNodeName(),
				'uri' => $uri,
				'active' => stristr($uri, $requestUriHost) !== FALSE ? TRUE : FALSE
			);
		}

		if ($domainsFound === FALSE) {
			$uri = $controllerContext->getUriBuilder()
				->reset()
				->setCreateAbsoluteUri(TRUE)
				->uriFor('index', array(), 'Backend\Backend', 'TYPO3.Neos');
			$sites[0]['uri'] = $uri;
		}

		return $sites;
	}

}
?>