<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\Factory\ProjectionFactoryDependencies;

interface ProjectionFactoryInterface
{
    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies, array $options, CatchUpHookFactoryInterface $catchUpHookFactory, Projections $projectionsSoFar): ProjectionInterface;
}
