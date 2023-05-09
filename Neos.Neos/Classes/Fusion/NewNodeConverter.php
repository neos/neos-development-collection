<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\RequestHandler;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

/**
 * !!! Only needed for uncached Fusion segments; as in Fusion ContentCache, the PropertyMapper is used to serialize
 * and deserialize the context.
 *
 * @Flow\Scope("singleton")
 * @deprecated
 */
class NewNodeConverter extends AbstractTypeConverter
{
    /**
     * @var array<int,string>
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = Node::class;

    /**
     * @var integer
     */
    protected $priority = 2;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;
    #[Flow\Inject]
    protected Bootstrap $bootstrap;

    /**
     * @param string $source
     * @param string $targetType
     * @param array<string,string> $subProperties
     * @return ?Node
     */
    public function convertFrom(
        $source,
        $targetType = null,
        array $subProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        $activeRequestHandler = $this->bootstrap->getActiveRequestHandler();
        $contentRepositoryId = ContentRepositoryId::fromString('default');
        if ($activeRequestHandler instanceof RequestHandler) {
            $httpRequest = $activeRequestHandler->getHttpRequest();
            $siteDetectionResult = SiteDetectionResult::fromRequest($httpRequest);
            $contentRepositoryId = $siteDetectionResult->contentRepositoryId;
        }

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
        $nodeAddress = $nodeAddressFactory->createFromUriString($source);

        $subgraph = $contentRepository->getContentGraph()
            ->getSubgraph(
                $nodeAddress->contentStreamId,
                $nodeAddress->dimensionSpacePoint,
                $nodeAddress->isInLiveWorkspace()
                    ? VisibilityConstraints::frontend()
                    : VisibilityConstraints::withoutRestrictions()
            );

        return $subgraph->findNodeById($nodeAddress->nodeAggregateId);
    }
}
