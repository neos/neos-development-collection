<?php

declare(strict_types=1);

namespace Neos\Neos\TypeConverter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\RequestHandler;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\AbstractTypeConverter;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

/**
 * To be removed legacy fragment for property mapping nodes in controllers.
 * MUST not be used and MUST be removed before Neos 9 release.
 * See issue: https://github.com/neos/neos-development-collection/issues/4873
 *
 * @Flow\Scope("singleton")
 * @deprecated must be removed before Neos 9 release!!!
 */
class HackyNodeAddressToNodeConverter extends AbstractTypeConverter
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
