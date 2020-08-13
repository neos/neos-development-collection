<?php
namespace Neos\Neos\Domain\Strategy;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Strategy\AbstractAssetUsageStrategy;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Model\Dto\AssetUsageInNodeProperties;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Utility\TypeHandling;

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
     * @return array<\Neos\Neos\Domain\Model\Dto\AssetUsageInNodeProperties>
     * @throws \Neos\ContentRepository\Exception\NodeConfigurationException
     */
    public function getUsageReferences(AssetInterface $asset)
    {
        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
        if (isset($this->firstlevelCache[$assetIdentifier])) {
            return $this->firstlevelCache[$assetIdentifier];
        }

        $relatedNodes = array_map(function (NodeData $relatedNodeData) use ($asset) {
            return new AssetUsageInNodeProperties(
                $asset,
                $relatedNodeData->getIdentifier(),
                $relatedNodeData->getWorkspace()->getName(),
                $relatedNodeData->getDimensionValues(),
                $relatedNodeData->getNodeType()->getName()
            );
        }, $this->getRelatedNodes($asset));

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

        return $this->nodeDataRepository->findNodesByPathPrefixAndRelatedEntities(SiteService::SITES_ROOT_PATH, $relationMap);
    }
}
