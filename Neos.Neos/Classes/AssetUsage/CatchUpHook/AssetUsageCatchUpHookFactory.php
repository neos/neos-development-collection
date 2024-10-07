<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\CatchUpHook;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\Neos\AssetUsage\Service\AssetUsageIndexingService;

class AssetUsageCatchUpHookFactory implements CatchUpHookFactoryInterface
{
    public function __construct(
        private AssetUsageIndexingService $assetUsageIndexingService
    ) {
    }

    public function build(ContentRepository $contentRepository): AssetUsageCatchUpHook
    {
        return new AssetUsageCatchUpHook(
            $contentRepository,
            $this->assetUsageIndexingService
        );
    }
}
