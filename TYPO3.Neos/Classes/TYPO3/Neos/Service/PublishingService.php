<?php
namespace TYPO3\Neos\Service;

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
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * The workspaces service adds some basic helper methods for getting workspaces,
 * unpublished nodes and methods for publishing nodes or whole workspaces.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PublishingService extends \TYPO3\TYPO3CR\Service\PublishingService {

	/**
	 * @Flow\Inject
	 * @var DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @var Domain
	 */
	protected $currentDomain = FALSE;

	/**
	 * @var Site
	 */
	protected $currentSite = FALSE;

	/**
	 * Publishes the given node to the specified target workspace. If no workspace is specified, "live" is assumed.
	 *
	 * @param NodeInterface $node
	 * @param Workspace $targetWorkspace If not set the "live" Workspace is assumed to be the publishing target
	 * @return void
	 * @api
	 */
	public function publishNode(NodeInterface $node, Workspace $targetWorkspace = NULL) {
		if ($targetWorkspace === NULL) {
			$targetWorkspace = $this->workspaceRepository->findOneByName('live');
		}
		$nodes = array($node);
		$nodeType = $node->getNodeType();
		if ($nodeType->isOfType('TYPO3.Neos:Document') || $nodeType->hasConfiguration('childNodes')) {
			foreach ($node->getChildNodes('TYPO3.Neos:ContentCollection') as $contentCollectionNode) {
				array_push($nodes, $contentCollectionNode);
			}
		}
		$sourceWorkspace = $node->getWorkspace();
		$sourceWorkspace->publishNodes($nodes, $targetWorkspace);

		$this->emitNodePublished($node, $targetWorkspace);
	}

	/**
	 * Creates a new content context based on the given workspace and the NodeData object and additionally takes
	 * the current site and current domain into account.
	 *
	 * @param Workspace $workspace Workspace for the new context
	 * @param array $dimensionValues The dimension values for the new context
	 * @param array $contextProperties Additional pre-defined context properties
	 * @return Context
	 */
	protected function createContext(Workspace $workspace, array $dimensionValues, array $contextProperties = array()) {
		if ($this->currentDomain === FALSE) {
			$this->currentDomain = $this->domainRepository->findOneByActiveRequest();
		}

		if ($this->currentDomain !== NULL) {
			$contextProperties['currentSite'] = $this->currentDomain->getSite();
			$contextProperties['currentDomain'] = $this->currentDomain;
		} else {
			if ($this->currentSite === FALSE) {
				$this->currentSite = $this->siteRepository->findOnline()->getFirst();
			}
			$contextProperties['currentSite'] = $this->currentSite;
		}

		return parent::createContext($workspace, $dimensionValues, $contextProperties);
	}

}