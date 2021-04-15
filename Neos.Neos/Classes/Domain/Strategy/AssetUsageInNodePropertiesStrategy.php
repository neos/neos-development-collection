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

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Dto\UsageReference;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Strategy\AssetUsageStrategyInterface;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Utility\TypeHandling;

/**
 * @Flow\Scope("singleton")
 */
final class AssetUsageInNodePropertiesStrategy implements AssetUsageStrategyInterface
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
     * @return array<UsageReference>
     * @throws \Neos\ContentRepository\Exception\NodeConfigurationException
     */
    public function getUsageReferences(AssetInterface $asset)
    {
        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
        if (isset($this->firstlevelCache[$assetIdentifier])) {
            return $this->firstlevelCache[$assetIdentifier];
        }

        $relatedNodes = array_map(static function (NodeData $node) {
            return new UsageReference(
                'TODO: dynamic label for node "' . $node->getName() . '"',
                new Uri('/todo'),
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

    public function getUsageCount(AssetInterface $asset): int
    {
        return count($this->getRelatedNodes($asset));
    }
}
