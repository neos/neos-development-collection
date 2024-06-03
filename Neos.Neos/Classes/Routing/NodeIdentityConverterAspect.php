<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Routing;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\Neos\FrontendRouting\NodeAddressFactory;

/**
 * Aspect to convert a node object to its context node path. This is used in URI
 * building in order to make linking to nodes a lot easier.
 *
 * On the long term, type converters should be able to convert the reverse direction
 * as well, and then this aspect could be removed.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class NodeIdentityConverterAspect
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Convert the object to its context path, if we deal with ContentRepository nodes.
     *
     * @Flow\Around("method(Neos\Flow\Persistence\AbstractPersistenceManager->convertObjectToIdentityArray())")
     * @param JoinPointInterface $joinPoint the joinpoint
     * @return string|array<string,string> the context path to be used for routing
     */
    public function convertNodeToContextPathForRouting(JoinPointInterface $joinPoint): array|string
    {
        $objectArgument = $joinPoint->getMethodArgument('object');
        if ($objectArgument instanceof Node) {
            $contentRepository = $this->contentRepositoryRegistry->get(
                $objectArgument->contentRepositoryId
            );
            $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
            $nodeAddress = $nodeAddressFactory->createFromNode($objectArgument);
            return ['__contextNodePath' => $nodeAddress->serializeForUri()];
        } elseif ($objectArgument instanceof NodeAddress) {
            return ['__contextNodePath' => $objectArgument->serializeForUri()];
        }

        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }
}
