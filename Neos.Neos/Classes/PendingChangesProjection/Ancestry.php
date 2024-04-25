<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\PendingChangesProjection;

use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * Ancestry projection state for change metadata
 *
 * Capable of providing essential metadata for changes, like
 ** what site does a change belong to
 ** what document does a change belong to
 ** where was something removed from
 * etc
 */
#[Flow\Proxy(false)]
final readonly class Ancestry implements ProjectionStateInterface
{
    public function __construct(
        private DbalClientInterface $client,
        private string $tableName
    ) {
    }
}
