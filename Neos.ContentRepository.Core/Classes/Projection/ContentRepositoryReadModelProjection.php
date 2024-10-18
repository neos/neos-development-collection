<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\ContentRepositoryReadModel;

/**
 * @extends ProjectionInterface<ContentRepositoryReadModel>
 * @api
 */
interface ContentRepositoryReadModelProjection extends ProjectionInterface
{
    public function getState(): ContentRepositoryReadModel;
}
