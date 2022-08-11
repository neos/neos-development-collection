<?php
declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\NodeAccessor;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;

/**
 * @internal
 */
#[Flow\Scope('singleton')]
class NodeAccessorChainFactory
{
    /**
     * @var array<string,array<string,int|string>>
     * @Flow\InjectConfiguration(path="nodeAccessorFactories")
     */
    protected $nodeAccessorFactoriesConfiguration;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    public function build(
        ContentSubgraphIdentity $contentSubgraphIdentity
    ): NodeAccessorInterface {
        $nodeAccessorFactoriesConfiguration = (new PositionalArraySorter($this->nodeAccessorFactoriesConfiguration))
            ->toArray();

        $nodeAccessorFactories = [];
        foreach ($nodeAccessorFactoriesConfiguration as $nodeAccessorFactoryConfiguration) {
            $nodeAccessorFactories[] = $this->objectManager->get($nodeAccessorFactoryConfiguration['className']);
        }

        // now, $nodeAccessorFactories contains a list of Factories,
        // where the FIRST factory creates the OUTERMOST NodeAccessor.
        // thus, we need to start creating the *last* NodeAccessor in the list; and then work ourselves upwards.
        $nextAccessor = null;
        foreach (array_reverse($nodeAccessorFactories) as $nodeAccessorFactory) {
            assert($nodeAccessorFactory instanceof NodeAccessorFactoryInterface);
            $nextAccessor = $nodeAccessorFactory->build(
                $contentSubgraphIdentity,
                $nextAccessor
            );
        }
        if (is_null($nextAccessor)) {
            throw new \Exception(
                'No node accessor factories were configured, please check the configuration',
                1645362731
            );
        }

        return $nextAccessor;
    }
}
