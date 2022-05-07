<?php
namespace Neos\Neos\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\ESCR\AssetUsage\Projector\AssetUsageRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * Takes care of cleaning up ImageVariants.
 *
 * @Flow\Scope("singleton")
 */
class ImageVariantGarbageCollector
{
    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    #[Flow\Inject]
    protected AssetUsageRepository $assetUsageRepository;

    /**
     * Removes unused ImageVariants after a Node property changes to a different ImageVariant.
     * This is triggered via the nodePropertyChanged event.
     *
     * Note: This method it triggered by the "nodePropertyChanged" signal,
     * @see \Neos\ContentRepository\Domain\Model\Node::emitNodePropertyChanged()
     *
     * @param NodeInterface $node the affected node
     * @param string $propertyName name of the property that has been changed/added
     * @param mixed $oldValue the property value before it was changed or NULL if the property is new
     * @param mixed $value the new property value
     * @return void
     */
    public function removeUnusedImageVariant(NodeInterface $node, $propertyName, $oldValue, $value)
    {
        if ($oldValue === $value || (!$oldValue instanceof ImageVariant)) {
            return;
        }
        $identifier = $this->persistenceManager->getIdentifierByObject($oldValue);
        $usage = $this->assetUsageRepository->findUsages(AssetUsageFilter::create()->withAsset($identifier));

        // This case shouldn't happen as the query will usually find at least the node that triggered this call,
        // still if there is no relation we can remove the ImageVariant.
        if ($usage->count() === 0) {
            $this->assetRepository->remove($oldValue);
            return;
        }

        if ($usage->count() === 1) {
            foreach ($usage->getIterator() as $usageItem) {
                // If the result contains exactly the node that got a new ImageVariant assigned
                // then we are safe to remove the asset here.
                if ($usageItem->contentStreamIdentifier === $node->getContentStreamIdentifier()
                    && $usageItem->originDimensionSpacePoint === $node->getOriginDimensionSpacePoint()->hash
                    && $usageItem->nodeAggregateIdentifier === $node->getNodeAggregateIdentifier()
                ) {
                    $this->assetRepository->remove($oldValue);
                }
            }
        }
    }
}
