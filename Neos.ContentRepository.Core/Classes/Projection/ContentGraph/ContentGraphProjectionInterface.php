<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Projection\ProjectionInterface;

/**
 * @extends ProjectionInterface<ContentGraphReadModelInterface>
 * @api for creating a custom content repository graph projection implementation, **not for users of the CR**
 */
interface ContentGraphProjectionInterface extends ProjectionInterface
{
    public function getState(): ContentGraphReadModelInterface;
}
