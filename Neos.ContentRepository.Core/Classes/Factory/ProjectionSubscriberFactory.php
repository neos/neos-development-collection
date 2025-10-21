<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Subscription\Subscriber\ProjectionSubscriber;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;

/**
 * @internal only API for custom content repository integrations
 */
final readonly class ProjectionSubscriberFactory
{
    /**
     * @param ProjectionFactoryInterface<ProjectionInterface<ProjectionStateInterface>> $projectionFactory
     * @param CatchUpHookFactoryInterface<ProjectionStateInterface>|null $catchUpHookFactory
     * @param array<string, mixed> $projectionFactoryOptions
     */
    public function __construct(
        private SubscriptionId $subscriptionId,
        private ProjectionFactoryInterface $projectionFactory,
        private ?CatchUpHookFactoryInterface $catchUpHookFactory,
        private array $projectionFactoryOptions,
    ) {
    }

    public function build(SubscriberFactoryDependencies $dependencies): ProjectionSubscriber
    {
        $projection = $this->projectionFactory->build($dependencies, $this->projectionFactoryOptions);
        $catchUpHook = $this->catchUpHookFactory?->build(CatchUpHookFactoryDependencies::create(
            $dependencies->contentRepositoryId,
            $projection->getState(),
            $dependencies->nodeTypeManager,
            $dependencies->contentDimensionSource,
            $dependencies->interDimensionalVariationGraph,
        ));

        return new ProjectionSubscriber(
            $this->subscriptionId,
            $projection,
            $catchUpHook,
        );
    }
}
