<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use DateTimeImmutable;

/**
 * Creation and modification timestamps of a node
 *
 * @api
 */
final class Timestamps
{
    /**
     * @param DateTimeImmutable $created When was the node created in its content stream
     * @param DateTimeImmutable $originalCreated When was the node created originally
     * @param ?DateTimeImmutable $lastModified When was the node last updated in its content stream, or NULL if it was never changed
     * @param ?DateTimeImmutable $originalLastModified When was the node last updated originally, or NULL if it was never changed
     */
    private function __construct(
        public readonly DateTimeImmutable $created,
        public readonly DateTimeImmutable $originalCreated,
        public readonly ?DateTimeImmutable $lastModified,
        public readonly ?DateTimeImmutable $originalLastModified,
    ) {
    }

    public static function create(
        DateTimeImmutable $created,
        DateTimeImmutable $originalCreated,
        ?DateTimeImmutable $lastModified,
        ?DateTimeImmutable $originalLastModified
    ): self {
        return new self($created, $originalCreated, $lastModified, $originalLastModified);
    }
}
