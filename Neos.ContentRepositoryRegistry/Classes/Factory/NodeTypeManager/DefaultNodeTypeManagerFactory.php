<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\NodeTypeManager;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\Configuration\NodeTypeEnrichmentService;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

class DefaultNodeTypeManagerFactory implements NodeTypeManagerFactoryInterface
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        private readonly ObjectManagerInterface $objectManager,
        private readonly NodeTypeEnrichmentService $nodeTypeEnrichmentService,
    )
    {
    }

    public function build(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $nodeTypeManagerPreset): NodeTypeManager
    {
        return new NodeTypeManager(
            function() {
                $configuration = $this->configurationManager->getConfiguration('NodeTypes');
                return $this->nodeTypeEnrichmentService->enrichNodeTypeLabelsConfiguration($configuration);
            },
            $this->objectManager,
            $nodeTypeManagerPreset['options']['fallbackNodeTypeName']
        );
    }
}
