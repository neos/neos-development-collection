<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Command;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Cli\CommandController;
use Neos\Neos\AssetUsage\AssetUsageIndexingProcessor;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

final class AssetUsageCommandController extends CommandController
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly AssetUsageIndexingProcessor $assetUsageIndexingProcessor
    ) {
        parent::__construct();
    }

    public function indexCommand(string $contentRepository = 'default', string $nodeTypeName = NodeTypeNameFactory::NAME_SITES): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $this->outputFormatted("Start indexing asset usages");

        $this->assetUsageIndexingProcessor->buildIndex(
            $contentRepository,
            NodeTypeName::fromString($nodeTypeName),
            function (string $message) {
                $this->outputFormatted($message);
            }
        );

        $this->outputFormatted("Finished.");
    }
}
