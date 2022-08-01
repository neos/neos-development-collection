<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection;

interface CatchUpHookFactoryInterface
{
    public function build(ProjectionStateInterface $projectionState): CatchUpHookInterface;
}
