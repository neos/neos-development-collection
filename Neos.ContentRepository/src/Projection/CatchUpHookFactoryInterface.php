<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\ContentRepository;

interface CatchUpHookFactoryInterface
{
    public function build(ContentRepository $contentRepository): CatchUpHookInterface;
}
