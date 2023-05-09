<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\NodeTypeManager;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\Configuration\NodeTypeEnrichmentService;
use Neos\Flow\Configuration\ConfigurationManager;

class DefaultNodeTypeManagerFactory implements NodeTypeManagerFactoryInterface
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        private readonly ObjectManagerBasedNodeLabelGeneratorFactory $nodeLabelGeneratorFactory,
        private readonly NodeTypeEnrichmentService $nodeTypeEnrichmentService,
    )
    {
    }

    public function build(ContentRepositoryId $contentRepositoryId, array $options): NodeTypeManager
    {
        return new NodeTypeManager(
            function () {
                $configuration = $this->configurationManager->getConfiguration('NodeTypes');
                return $this->nodeTypeEnrichmentService->enrichNodeTypeLabelsConfiguration($configuration);
            },
            $this->nodeLabelGeneratorFactory,
            $options['fallbackNodeTypeName'] ?? null,
        );
    }
}
