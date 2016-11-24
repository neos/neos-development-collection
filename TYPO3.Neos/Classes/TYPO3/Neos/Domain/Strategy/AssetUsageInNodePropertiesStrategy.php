<?php
namespace TYPO3\Neos\Domain\Strategy;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Utility\TypeHandling;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Strategy\AbstractAssetUsageStrategy;
use TYPO3\Neos\Domain\Model\Dto\AssetUsageInNodeProperties;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Service\UserService;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Neos\Controller\CreateContentContextTrait;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\Neos\Domain\Service\UserService as DomainUserService;

/**
 * @Flow\Scope("singleton")
 */
class AssetUsageInNodePropertiesStrategy extends AbstractAssetUsageStrategy
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var DomainUserService
     */
    protected $domainUserService;

    /**
     * @var array
     */
    protected $firstlevelCache = [];

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Returns an array of usage reference objects.
     *
     * @param AssetInterface $asset
     * @return array<\TYPO3\Neos\Domain\Model\Dto\AssetUsageInNodeProperties>
     * @throws \TYPO3\TYPO3CR\Exception\NodeConfigurationException
     */
    public function getUsageReferences(AssetInterface $asset)
    {
        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
        if (isset($this->firstlevelCache[$assetIdentifier])) {
            return $this->firstlevelCache[$assetIdentifier];
        }

        $userWorkspace = $this->userService->getPersonalWorkspace();

        $relatedNodes = [];
        foreach ($this->getRelatedNodes($asset) as $relatedNodeData) {
            $accessible = $this->domainUserService->currentUserCanReadWorkspace($relatedNodeData->getWorkspace());
            if ($accessible) {
                $context = $this->createContextMatchingNodeData($relatedNodeData);
            } else {
                $context = $this->createContentContext($userWorkspace->getName());
            }
            $site = $context->getCurrentSite();
            $node = $this->nodeFactory->createFromNodeData($relatedNodeData, $context);
            $flowQuery = new FlowQuery([$node]);
            /** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $documentNode */
            $documentNode = $flowQuery->closest('[instanceof TYPO3.Neos:Document]')->get(0);

            $relatedNodes[] = new AssetUsageInNodeProperties($asset, $site, $documentNode, $node, $accessible);
        }

        $this->firstlevelCache[$assetIdentifier] = $relatedNodes;
        return $this->firstlevelCache[$assetIdentifier];
    }

    /**
     * Returns all nodes that use the asset in a node property.
     *
     * @param AssetInterface $asset
     * @return array
     */
    public function getRelatedNodes(AssetInterface $asset)
    {
        $relationMap = [];
        $relationMap[TypeHandling::getTypeForValue($asset)] = [$this->persistenceManager->getIdentifierByObject($asset)];

        if ($asset instanceof Image) {
            foreach ($asset->getVariants() as $variant) {
                $type = TypeHandling::getTypeForValue($variant);
                if (!isset($relationMap[$type])) {
                    $relationMap[$type] = [];
                }
                $relationMap[$type][] = $this->persistenceManager->getIdentifierByObject($variant);
            }
        }

        return $this->nodeDataRepository->findNodesByRelatedEntities($relationMap);
    }
}
