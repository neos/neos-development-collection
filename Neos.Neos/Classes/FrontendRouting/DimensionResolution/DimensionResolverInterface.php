<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\FrontendRouting\DimensionResolution\Resolver\CompositeResolver;
use Neos\Neos\FrontendRouting\EventSourcedFrontendNodeRoutePartHandler;
use Neos\Neos\FrontendRouting\Projection\DocumentNodeInfo;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Domain\Model\Site;

/**
 * Common interface for content dimension resolvers which are hooked into the Frontend Routing.
 * They are responsible for two concerns:
 *
 * 1) At the beginning of a request, during the Routing stage, to figure out the DimensionSpacePoint
 *    which should be shown from the URL (by looking at the first URI Path Segment, or the Hostname, or ...)
 *
 * 2) During Link rendering, to add the dimension part to the URL in the appropriate way.
 *
 * There is always a *single* {@see DimensionResolverInterface} active; though this implementation
 * can delegate to other implementations of this interface - thus it is built to be extensible
 * via *composition*.
 *
 * **For a high-level overview on the Frontend Routing, visit {@see EventSourcedFrontendNodeRoutePartHandler}.**
 *
 *
 * ## Usage of {@see DimensionResolverInterface::fromRequestToDimensionSpacePoint}
 *
 * This method is called during the Routing stage (1) above. It receives a {@see RequestToDimensionSpacePointContext},
 * through which the current URL and route parameters can be received; and via {@see SiteDetectionResult},
 * also the current site / content repository / hostname are accessible.
 *
 * The {@see RequestToDimensionSpacePointContext::withAddedDimensionSpacePoint()} method should be called with
 * the resolving result, potentially multiple times. NOTE: {@see RequestToDimensionSpacePointContext} is immutable,
 * so calling a method like the above returns a *new instance* which you need to properly return to the
 * caller.
 *
 *
 * ## Usage of {@see DimensionResolverInterface::fromDimensionSpacePointToUriConstraints}
 *
 * This method is called during the Link rendering (2) above. It receives a DimensionSpacePoint and returns
 * an UriConstraints object which contains the necessary URL modifications.
 *
 *
 * ## Site-specific configuration through Settings.yaml
 *
 * In Settings.yaml, underneath the path
 * `Neos.Neos.sites.[site-node-name|*].contentDimensions.resolver.factoryClassName`,
 * you specify which {@see DimensionResolverFactoryInterface} should be used to build the used
 * {@see DimensionResolverInterface}.
 *
 * The settings path can either:
 * - contain a **specific site node name** - in this case,
 *   the dimension config etc is **just used for the single site.**
 * - contain a fallback `*` for all other cases.
 *
 * The referenced factories are usually singletons; and you can process configuration and instantiate the actual
 * {@see DimensionResolverInterface} instances in any way you want. You need to pass in all dependencies the resolver
 * needs.
 *
 * The delegation logic described above is implemented in {@see DelegatingResolver}, which is directly referenced
 * in {@see EventSourcedFrontendNodeRoutePartHandler}.
 *
 *
 * ## {@see DimensionResolverFactoryInterface} and {@see CompositeResolver}
 *
 * For many use cases where you want to **compose different resolvers** (e.g. use UriPathResolver for
 * the `language` dimension; and the HostnameResolver for the `country` dimension), you have to write a
 * custom {@see DimensionResolverFactoryInterface}, where you can instantiate your desired resolvers with
 * the configuration you need.
 *
 * To form a **chain of resolvers**, you can all the different resolver instances with the {@see CompositeResolver}.
 *
 * For even more advanced cases, you will need a custom {@see DimensionResolverFactoryInterface} and a corresponding
 * {@see DimensionResolverInterface}.
 *
 *
 * ## Composition of {@see DimensionResolverInterface}
 *
 * As written above, it is common that a resolver delegates to one or more other resolvers.
 * To ensure that this composition will work nicely, **it is prohibited inside Resolvers to read Settings
 * or any other global state**. If you need to access global state, read it in the Factory and pass it
 * via Constructor to the Resolvers.
 *
 * (Note: This rule does not apply to {@see DelegatingResolver},
 * which is the hardcoded entrypoint to the resolver list.)
 *
 * @api
 */
interface DimensionResolverInterface
{
    /**
     * Called in the incoming direction, when an URL is resolved on its way
     *
     * @param RequestToDimensionSpacePointContext $context
     * @return RequestToDimensionSpacePointContext the modified context
     */
    public function fromRequestToDimensionSpacePoint(
        RequestToDimensionSpacePointContext $context
    ): RequestToDimensionSpacePointContext;

    /**
     * Called for each generated URL, to adjust (and return) the passed-in UriConstraints
     * depending on the given DimensionSpacePoint.
     *
     * @param DimensionSpacePoint $filteredDimensionSpacePoint
     * @param DocumentNodeInfo $targetNodeInfo
     * @param UriConstraints $uriConstraints the pre-applied uriConstraints -> modify and return them.
     * @return UriConstraints
     */
    public function fromDimensionSpacePointToUriConstraints(
        DimensionSpacePoint $filteredDimensionSpacePoint,
        DocumentNodeInfo $targetNodeInfo,
        Site $targetSite,
        UriConstraints $uriConstraints,
    ): UriConstraints;
}
