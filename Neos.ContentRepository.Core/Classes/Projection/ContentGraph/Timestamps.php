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
 * * `created`: Date and time a node was created in its content stream
 * * `originalCreated`: Date and time a node was created originally (this is equal to `created` for nodes in the original content stream)
 * * `lastModified`: Date and time a node was last modified in its content stream (NULL = never modified)
 * * `originalLastModified` Date and time a node was last modified in its original content stream
 *
 * When a node is originally created via one of the following events:
 * * {@see RootNodeAggregateWithNodeWasCreated}
 * * {@see NodeAggregateWithNodeWasCreated}
 * the `created` and `originalCreated` values are set to the current date and time.
 *
 * When the creation even is re-applied on a different content stream (i.e. when publishing a node initially)
 * or when a node variant is created via one of the following events:
 * * {@see NodeSpecializationVariantWasCreated}
 * * {@see NodeGeneralizationVariantWasCreated}
 * * {@see NodePeerVariantWasCreated}
 * the `created` value is set to the current date and time while the `originalCreated` value is copied over
 * from the already existing node.
 *
 * The `lastModified` and `originalLastModified` values will be NULL upon creation. This allows for checking whether a node has been modified
 * at all.
 *
 * When a node is being modified via one of the following events:
 * * {@see NodeAggregateNameWasChanged}
 * * {@see NodePropertiesWereSet}
 * * {@see NodeReferencesWereSet}
 * * {@see NodeAggregateTypeWasChanged}
 * the `lastModified` is set to the current date and time.
 * the `originalLastModified` value is also set to the current date and time if the node is modified in the original content stream.
 * Otherwise, it is copied over from the original event
 *
 * To order nodes by their timestamps, the {@see TimestampField} can be used (@see Ordering)
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

    /**
     * Returns a new copy with the specified new values
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public function with(
        DateTimeImmutable $created = null,
        DateTimeImmutable $originalCreated = null,
        DateTimeImmutable $lastModified = null,
        DateTimeImmutable $originalLastModified = null,
    ): self {
        return new self(
            $created ?? $this->created,
            $originalCreated ?? $this->originalCreated,
            $lastModified ?? $this->lastModified,
            $originalLastModified ?? $this->originalLastModified,
        );
    }
}
