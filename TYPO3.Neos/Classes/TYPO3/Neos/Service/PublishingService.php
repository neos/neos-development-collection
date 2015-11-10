<?php
namespace TYPO3\Neos\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
class PublishingService extends \TYPO3\TYPO3CR\Service\PublishingService
{
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
    protected $currentDomain = false;

    /**
     * @var Site
     */
    protected $currentSite = false;

    /**
     * Publishes the given node to the specified target workspace. If no workspace is specified, "live" is assumed.
     *
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace If not set the "live" Workspace is assumed to be the publishing target
     * @return void
     * @api
     */
    public function publishNode(NodeInterface $node, Workspace $targetWorkspace = null)
    {
        if ($targetWorkspace === null) {
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
    protected function createContext(Workspace $workspace, array $dimensionValues, array $contextProperties = array())
    {
        if ($this->currentDomain === false) {
            $this->currentDomain = $this->domainRepository->findOneByActiveRequest();
        }

        if ($this->currentDomain !== null) {
            $contextProperties['currentSite'] = $this->currentDomain->getSite();
            $contextProperties['currentDomain'] = $this->currentDomain;
        } else {
            if ($this->currentSite === false) {
                $this->currentSite = $this->siteRepository->findFirstOnline();
            }
            $contextProperties['currentSite'] = $this->currentSite;
        }

        return parent::createContext($workspace, $dimensionValues, $contextProperties);
    }
}
