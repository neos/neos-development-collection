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

    /**
     * @param string $contentRepository The content repository identifier. (Default: 'default')
     * @param string $nodeTypeName Name of the root nodetype (Default: 'Neos.Neos:Sites')
     * @param bool $force Flag to enforce the index creation also with outdated workspaces with unpublished changes
     * @return void
     */
    public function indexCommand(string $contentRepository = 'default', string $nodeTypeName = NodeTypeNameFactory::NAME_SITES, bool $force = false): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);

        $this->outputFormatted("Start indexing asset usages");

        $successful = $this->assetUsageIndexingProcessor->buildIndex(
            $this->contentRepositoryRegistry->get($contentRepositoryId),
            NodeTypeName::fromString($nodeTypeName),
            $force,
            function (string $message) {
                $this->outputFormatted($message);
            }
        );

        if ($successful) {
            $this->outputFormatted("Finished.");
        } else {
            $this->outputFormatted("An error occured while indexing asset usages.");
            $this->quit(1);
        }
    }
}
