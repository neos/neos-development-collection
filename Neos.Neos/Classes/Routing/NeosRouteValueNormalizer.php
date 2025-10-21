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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\FlowPersistenceRouteValuesNormalizer;
use Neos\Flow\Mvc\Routing\RouteValuesNormalizerInterface;
use Neos\Neos\TypeConverter\NodeAddressToNodeConverter;

/**
 * Route value normaliser to additionally handle node objects and convert them to node address strings
 *
 * This is used in URI building in order to simplify resolving routes with node object route values
 *
 * Resolving routes
 * ----------------
 *
 * Flows routing by default only accepts either primitive php values or flow entities which are encoded via the
 * {@see FlowPersistenceRouteValuesNormalizer} to array('__identity' => <persistent-object-id>)
 * So only the use of a simple json node address works by default:
 *
 *    $uriBuilder->uriFor(
 *         'someThing',
 *         ['node' => NodeAddress::fromNode($source)->toJson()]
 *     );
 *
 * A non flow entity object will raise an exception: Tried to convert an object of type "Node" to an identity array, but it is unknown to the Persistence Manager.
 * To allow actual node instances passed we convert the node to its json node address.
 *
 *    $uriBuilder->uriFor(
 *         'someThing',
 *         ['node' => $node]
 *    );
 *
 * Matching routes
 * ---------------
 *
 * When invoking the controller the route values are transformed based on the actions' signature.
 * The following action will invoke the {@see NodeAddressToNodeConverter}.
 *
 *     public function indexAction(Node $node);
 *
 * @internal
 * @Flow\Scope("singleton")
 */
class NeosRouteValueNormalizer implements RouteValuesNormalizerInterface
{
    public function __construct(
        private FlowPersistenceRouteValuesNormalizer $flowPersistenceRouteValuesNormalizer
    ) {
    }

    /**
     * @param array<mixed> $array The array to be iterated over
     * @return array<mixed> The modified array without objects
     */
    public function normalizeObjects(array $array): array
    {
        foreach ($array as $key => $value) {
            // convert for simple cases first level nodes or node address' to json string
            if ($value instanceof Node) {
                $array[$key] = NodeAddress::fromNode($value)->toJson();
            } elseif ($value instanceof NodeAddress) {
                $array[$key] = $value->toJson();
            }
        }
        return $this->flowPersistenceRouteValuesNormalizer->normalizeObjects($array);
    }
}
