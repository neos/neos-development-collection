<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\ContentGraphReadModelInterface;

/**
 * @extends ProjectionInterface<ContentGraphReadModelInterface>
 * @api for creating a custom content repository graph and workspace implementation, **not for users of the CR**
 */
interface ContentGraphProjectionInterface extends ProjectionInterface
{
    public function getState(): ContentGraphReadModelInterface;
}
