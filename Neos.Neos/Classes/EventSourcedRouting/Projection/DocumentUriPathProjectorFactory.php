<?php

declare(strict_types=1);

namespace Neos\Neos\EventSourcedRouting\Projection;

use Neos\Flow\Annotations as Flow;
use Doctrine\DBAL\Connection;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;

// NOTE: as workaround, we cannot reflect this class (because of an overly eager DefaultEventToListenerMappingProvider in
// Neos.EventSourcing - which will be refactored soon). That's why we need an extra factory (this class)
// See Neos.ContentRepositoryRegistry/Configuration/Settings.hacks.yaml for further details.
/**
 * @Flow\Scope("singleton")
 */
final class DocumentUriPathProjectorFactory
{
    public function __construct(
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly DocumentUriPathFinder $documentUriPathFinder,
        private readonly Connection $dbal
    ) {
    }

    public function build(): DocumentUriPathProjector
    {
        return new DocumentUriPathProjector(
            $this->nodeTypeManager,
            $this->documentUriPathFinder,
            $this->dbal
        );
    }
}
