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

namespace Neos\Neos\Domain\Pruning;

use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;

/**
 * Pruning processor that removes all events from the given cr and resets the projections
 */
final readonly class ContentRepositoryPruningProcessor implements ProcessorInterface
{
    public function __construct(
        private ContentRepositoryMaintainer $contentRepositoryMaintainer,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $result = $this->contentRepositoryMaintainer->prune();
        if ($result !== null) {
            throw new \RuntimeException($result->getMessage(), 1732461335);
        }
    }
}
