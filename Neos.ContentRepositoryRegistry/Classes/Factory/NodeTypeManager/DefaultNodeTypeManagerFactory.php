<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\NodeTypeManager;

use Neos\ContentRepository\Core\NodeType\ClosureNodeTypeProvider;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Configuration\NodeTypeEnrichmentService;
use Neos\Flow\Configuration\ConfigurationManager;

readonly class DefaultNodeTypeManagerFactory implements NodeTypeManagerFactoryInterface
{
    public function __construct(
        private ConfigurationManager $configurationManager,
        private NodeTypeEnrichmentService $nodeTypeEnrichmentService,
    ) {
    }

    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): NodeTypeManager
    {
        return new NodeTypeManager(
            new ClosureNodeTypeProvider(
                function () {
                    $configuration = $this->configurationManager->getConfiguration('NodeTypes');
                    return $this->nodeTypeEnrichmentService->enrichNodeTypeLabelsConfiguration($configuration);
                }
            )
        );
    }
}
