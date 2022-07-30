<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Controller\Module\Management;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;

class WorkspacesControllerInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        protected ContentDimensionSourceInterface $contentDimensionSource,
    )
    {
    }

    public function getContentDimensionsOrderedByPriority(): array
    {
        return $this->contentDimensionSource->getContentDimensionsOrderedByPriority();
    }
}
