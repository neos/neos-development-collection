<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\ContentRepository;

/**
 * @api
 */
interface CatchUpHookFactoryInterface
{
    public function build(ContentRepository $contentRepository): CatchUpHookInterface;
}
