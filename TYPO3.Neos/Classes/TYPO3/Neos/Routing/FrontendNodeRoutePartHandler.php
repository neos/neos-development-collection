<?php
namespace TYPO3\Neos\Routing;

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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 *
 * @Flow\Scope("singleton")
 */
class FrontendNodeRoutePartHandler extends \TYPO3\Flow\Mvc\Routing\DynamicRoutePart {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\NodeService
	 */
	protected $nodeService;

	/**
	 * Matches a frontend URI pointing to a node (for example a page).
	 *
	 * This function tries to find a matching node by the given relative context node path. If one was found, its
	 * absolute context node path is returned in $this->value.
	 *
	 * Note that this matcher does not check if access to the resolved workspace or node is allowed because at the point
	 * in time the route part handler is invoked, the security framework is not yet fully initialized.
	 *
	 * @param string $requestPath The relative context node path (without leading "/", relative to the current Site Node)
	 * @return boolean TRUE if the $requestPath could be matched, otherwise FALSE
	 * @throws \TYPO3\Neos\Routing\Exception\NoHomepageException if no node could be found on the homepage (empty $requestPath)
	 */
	protected function matchValue($requestPath) {
		try {
			$node = $this->nodeService->getNodeByContextNodePath($requestPath);
		} catch (\TYPO3\Neos\Routing\Exception $exception) {
			if ($requestPath === '') {
				throw new \TYPO3\Neos\Routing\Exception\NoHomepageException('Homepage could not be loaded. Probably you haven\'t imported a site yet', 1346950755, $exception);
			}
			return FALSE;
		}

		$this->value = $node->getContextPath();
		return TRUE;
	}

	/**
	 * Extracts the node path from the request path.
	 *
	 * @param string $requestPath The request path to be matched
	 * @return string value to match, or an empty string if $requestPath is empty or split string was not found
	 */
	protected function findValueToMatch($requestPath) {
		if ($this->splitString !== '') {
			$splitStringPosition = strpos($requestPath, $this->splitString);
			if ($splitStringPosition !== FALSE) {
				$requestPath = substr($requestPath, 0, $splitStringPosition);
			}
		}
		if (strpos($requestPath, '.') === FALSE) {
			return $requestPath;
		} else {
			$splitRequestPath = explode('/', $requestPath);
			$lastPart = array_pop($splitRequestPath);
			$dotPosition = strpos($lastPart, '.');
			if ($dotPosition !== FALSE) {
				$lastPart = substr($lastPart, 0, $dotPosition);
			}
			array_push($splitRequestPath, $lastPart);
			return implode('/', $splitRequestPath);
		}
	}

	/**
	 * Checks, whether given value is a Node object and if so, sets $this->value to the respective node context path.
	 *
	 * In order to render a suitable frontend URI, this function strips off the path to the site node and only keeps
	 * the actual node path relative to that site node. In practice this function would set $this->value as follows:
	 *
	 * absolute node path: /sites/neostypo3org/homepage/about
	 * $this->value:       homepage/about
	 *
	 * absolute node path: /sites/neostypo3org/homepage/about@user-admin
	 * $this->value:       homepage/about@user-admin

	 *
	 * @param mixed $value Either a Node object or an absolute context node path
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 */
	protected function resolveValue($value) {
		if (!$value instanceof NodeInterface && !is_string($value)) {
			return FALSE;
		}

		if (is_string($value)) {
			preg_match(NodeInterface::MATCH_PATTERN_CONTEXTPATH, $value, $matches);
			if (!isset($matches['NodePath'])) {
				return FALSE;
			}

			$contentContext = $this->nodeRepository->getContext();
			if ($contentContext->getWorkspace(FALSE) === NULL) {
				return FALSE;
			}

			$node = $contentContext->getCurrentSiteNode()->getNode($matches['NodePath']);
		} else {
			$node = $value;
			$contentContext = $this->nodeRepository->getContext();
		}

		if ($node instanceof NodeInterface) {
			while ($node->getContentType()->isOfType('TYPO3.Neos.ContentTypes:Shortcut')) {
				$childNodes = $node->getChildNodes('TYPO3.Neos.ContentTypes:Page,TYPO3.Neos.ContentTypes:Shortcut');
				$node = current($childNodes);
			}

			$nodeContextPath = $node->getContextPath();
			$siteNodePath = $contentContext->getCurrentSiteNode()->getPath();
		} else {
			return FALSE;
		}

		if (substr($nodeContextPath, 0, strlen($siteNodePath)) !== $siteNodePath) {
			return FALSE;
		}

		$this->value = substr($nodeContextPath, strlen($siteNodePath) + 1);
		return TRUE;
	}

}
?>