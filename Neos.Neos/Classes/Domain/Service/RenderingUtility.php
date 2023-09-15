<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Fusion\Core\FusionGlobals;
use Neos\Neos\Domain\Model\FusionRenderingStuff;
use Neos\Neos\Domain\Model\RenderingContext;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
class RenderingUtility
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly SiteRepository $siteRepository
    ) {
    }

    public function createRenderingContextForEntryNode(Node $entryNode): RenderingContext
    {
        $contentRepository = $this->contentRepositoryRegistry->get($entryNode->subgraphIdentity->contentRepositoryId);
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($entryNode);

        $documentNodeAncestors = $subgraph->findAncestorNodes(
            $entryNode->nodeAggregateId,
            FindAncestorNodesFilter::create(nodeTypeConstraints: NodeTypeNameFactory::NAME_DOCUMENT)
        );

        $nodeType = $contentRepository->getNodeTypeManager()->getNodeType($entryNode->nodeTypeName);

        $nodeIsDocument = $nodeType->isOfType(NodeTypeNameFactory::NAME_DOCUMENT);

        return new RenderingContext(
            $entryNode,
            $nodeIsDocument ? $entryNode : $documentNodeAncestors->first(),
            $documentNodeAncestors->last() ?? $entryNode
        );
    }

    public function createFusionRenderingStuff(Node $node, ActionRequest $request): FusionRenderingStuff
    {
        $renderingContext = $this->createRenderingContextForEntryNode($node);

        $site = $this->siteRepository->findSiteBySiteNode($renderingContext->siteNode);

        $fusionGlobals = FusionGlobals::fromArray([
            'request' => $request
        ]);

        return new FusionRenderingStuff(
            $site,
            $renderingContext,
            $fusionGlobals
        );
    }
}
