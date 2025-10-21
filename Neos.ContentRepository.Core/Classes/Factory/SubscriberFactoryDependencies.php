<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * @api because it is used inside the ProjectionsFactory
 */
final readonly class SubscriberFactoryDependencies
{
    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public NodeTypeManager $nodeTypeManager,
        public ContentDimensionSourceInterface $contentDimensionSource,
        public InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private PropertyConverter $propertyConverter,
    ) {
    }

    /**
     * @internal
     */
    public static function create(
        ContentRepositoryId $contentRepositoryId,
        NodeTypeManager $nodeTypeManager,
        ContentDimensionSourceInterface $contentDimensionSource,
        InterDimensionalVariationGraph $interDimensionalVariationGraph,
        PropertyConverter $propertyConverter
    ): self {
        return new self(
            $contentRepositoryId,
            $nodeTypeManager,
            $contentDimensionSource,
            $interDimensionalVariationGraph,
            $propertyConverter
        );
    }

    /**
     * @internal only to be used for custom content graph integrations to build a node property collection
     */
    public function getPropertyConverter(): PropertyConverter
    {
        return $this->propertyConverter;
    }
}
