<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Controller;

use Neos\ContentRepository\Core\Dimension\ContentDimension;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;

/**
 * @deprecated really un-nice :D
 */
class WorkspaceControllerInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        protected ContentDimensionSourceInterface $contentDimensionSource,
    ) {
    }

    /**
     * @return array<string,ContentDimension>
     */
    public function getContentDimensionsOrderedByPriority(): array
    {
        return $this->contentDimensionSource->getContentDimensionsOrderedByPriority();
    }
}
