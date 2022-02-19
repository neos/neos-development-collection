<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\ContentAccess;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;

/**
 * @internal
 */
#[Flow\Scope("singleton")]
class NodeAccessorChainFactory
{
    /**
     * @var array
     * @Flow\InjectConfiguration(path="nodeAccessorFactories")
     */
    protected $nodeAccessorFactoriesConfiguration;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    public function build(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
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
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $visibilityConstraints,
                $nextAccessor
            );
        }

        return $nextAccessor;
    }
}
