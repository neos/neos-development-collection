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

namespace Neos\Neos\Service;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\AssetUsage\Dto\AssetUsageReference;

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
    protected AssetService $assetService;

    /**
     * Removes unused ImageVariants after a Node property changes to a different ImageVariant.
     * This is triggered via the nodePropertyChanged event.
     *
     * Note: This method it triggered by the "nodePropertyChanged" signal,
     * @see \Neos\ContentRepository\Domain\Model\Node::emitNodePropertyChanged()
     * TODO Fix with Neos9 !!! See https://github.com/neos/neos-development-collection/issues/5145
     *
     * @param Node $node the affected node
     * @param string $propertyName name of the property that has been changed/added
     * @param mixed $oldValue the property value before it was changed or NULL if the property is new
     * @param mixed $value the new property value
     * @return void
     */
    public function removeUnusedImageVariant(Node $node, $propertyName, $oldValue, $value)
    {
        if ($oldValue === $value || (!$oldValue instanceof ImageVariant)) {
            return;
        }
        $usageCount = $this->assetService->getUsageCount($oldValue);

        // This case shouldn't happen as the query will usually find at least the node that triggered this call,
        // still if there is no relation we can remove the ImageVariant.
        if ($usageCount === 0) {
            $this->assetRepository->remove($oldValue);
            return;
        }

        if ($usageCount === 1) {
            foreach ($this->assetService->getUsageReferences($oldValue) as $usageItem) {
                // If the result contains exactly the node that got a new ImageVariant assigned
                // then we are safe to remove the asset here.
                if (
                    $usageItem instanceof AssetUsageReference
                    /** @phpstan-ignore-next-line todo needs repair see https://github.com/neos/neos-development-collection/issues/5145 */
                    && $usageItem->getWorkspaceName()->equals($node->workspaceName)
                    && $usageItem->getOriginDimensionSpacePoint()->equals($node->originDimensionSpacePoint)
                    && $usageItem->getNodeAggregateId()->equals($node->aggregateId)
                ) {
                    $this->assetRepository->remove($oldValue);
                }
            }
        }
    }
}
