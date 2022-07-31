<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection;

interface CatchUpHandlerFactoryInterface
{
    public function build(ProjectionStateInterface $projectionState): CatchUpHandlerInterface;
}
