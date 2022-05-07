<?php
namespace Neos\Neos\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

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
    protected NodeAddressFactory $nodeAddressFactory;

    /**
     * Convert the object to its context path, if we deal with ContentRepository nodes.
     *
     * @Flow\Around("method(Neos\Flow\Persistence\AbstractPersistenceManager->convertObjectToIdentityArray())")
     * @param JoinPointInterface $joinPoint the joinpoint
     * @return string|array<string,string> the context path to be used for routing
     */
    public function convertNodeToContextPathForRouting(JoinPointInterface $joinPoint): array
    {
        $objectArgument = $joinPoint->getMethodArgument('object');
        if ($objectArgument instanceof NodeInterface) {
            $nodeAddress = $this->nodeAddressFactory->createFromNode($objectArgument);
            return ['__contextNodePath' => $nodeAddress->serializeForUri()];
        }

        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }
}
