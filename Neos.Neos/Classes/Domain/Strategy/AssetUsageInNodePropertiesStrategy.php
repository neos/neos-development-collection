<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Strategy;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ESCR\AssetUsage\Dto\AssetUsage;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\ESCR\AssetUsage\Projector\AssetUsageRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Strategy\AbstractAssetUsageStrategy;
use Neos\Neos\Domain\Model\Dto\AssetUsageInNodeProperties;

#[Flow\Scope('singleton')]
class AssetUsageInNodePropertiesStrategy extends AbstractAssetUsageStrategy
{
    /**
     * @var array<string,array<AssetUsageInNodeProperties>>
     */
    protected array $firstlevelCache = [];

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected AssetUsageRepository $assetUsageRepository;

    // TODO FIX ME
    //#[Flow\Inject]
    //protected WorkspaceFinder $workspaceFinder;

    //#[Flow\Inject]
    //protected ContentDimensionZookeeper $contentDimensionZookeeper;

    /**
     * Returns an array of usage reference objects.
     *
     * @return array<string,AssetUsageInNodeProperties>
     * @throws \Neos\ContentRepository\Feature\Common\NodeConfigurationException
     */
    public function getUsageReferences(AssetInterface $asset): array
    {
        $dimensionSpacePointSet = $this->contentDimensionZookeeper->getAllowedDimensionSubspace();

        $relatedNodes = array_map(function (AssetUsage $assetUsage) use (
            $asset,
            $dimensionSpacePointSet
        ): AssetUsageInNodeProperties {
            $dimensionSpacePoint = $dimensionSpacePointSet->points[$assetUsage->originDimensionSpacePoint] ?? null;
            return new AssetUsageInNodeProperties(
                $asset,
                (string)$assetUsage->nodeAggregateIdentifier,
                $this->workspaceFinder->findOneByCurrentContentStreamIdentifier(
                    $assetUsage->contentStreamIdentifier
                )?->workspaceName ?: '',
                $dimensionSpacePoint?->coordinates ?? [],
                ''
            );
        }, iterator_to_array($this->assetUsageRepository->findUsages(AssetUsageFilter::create()->withAsset(
            $this->persistenceManager->getIdentifierByObject($asset)
        ))->getIterator()));

        /** @var string $assetIdentifier */
        $assetIdentifier = $this->persistenceManager->getIdentifierByObject($asset);
        $this->firstlevelCache[$assetIdentifier] = $relatedNodes;

        return $this->firstlevelCache[$assetIdentifier];
    }
}
