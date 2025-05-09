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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;

/**
 * The visibility constraints define how nodes in the content subgraph are accessed.
 *
 * For this the constraints need to be provided to {@see ContentGraphInterface::getSubgraph()}.
 * Alternatively {@see ContentRepository::getContentSubgraph()} uses the implemented {@see AuthProviderInterface} to determine
 * the visibility constraint the current applications state via {@see AuthProviderInterface::getVisibilityConstraints()}
 *
 * To have nodes for example with the tag `my-disabled` excluded use:
 *
 *     VisibilityConstraints::excludeSubtreeTags(SubtreeTags::create(
 *         SubtreeTag::fromString("my-disabled")
 *     ));
 *
 * But to access them no constraints can be used includes those:
 *
 *     VisibilityConstraints::createEmpty();
 *
 * @api
 */
final readonly class VisibilityConstraints implements \JsonSerializable
{
    /**
     * @param SubtreeTags $excludedSubtreeTags A set of {@see SubtreeTag} instances that will be _excluded_ from the results of any content graph query
     */
    private function __construct(
        public SubtreeTags $excludedSubtreeTags,
    ) {
    }

    /**
     * A subgraph without constraints for finding all nodes without filtering
     */
    public static function createEmpty(): self
    {
        return new self(SubtreeTags::createEmpty());
    }

    /**
     * @param SubtreeTags $subtreeTags A set of {@see SubtreeTag} instances that will be _excluded_ from the results of any content graph query
     */
    public static function excludeSubtreeTags(SubtreeTags $subtreeTags): self
    {
        return new self($subtreeTags);
    }

    public function getHash(): string
    {
        return md5(implode('|', $this->excludedSubtreeTags->toStringArray()));
    }

    public function merge(VisibilityConstraints $other): self
    {
        return new self($this->excludedSubtreeTags->merge($other->excludedSubtreeTags));
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    /** -------------- deprecations: ------------------- */

    /**
     * Please use {@see VisibilityConstraints::excludeSubtreeTags} instead.
     *
     * @deprecated with Neos 9 beta 19. To be removed with Neos 10.
     */
    public static function fromTagConstraints(SubtreeTags $tagConstraints): self
    {
        return self::excludeSubtreeTags($tagConstraints);
    }

    /**
     * Legacy, only for Neos.Neos context!, for standalone use please use {@see self::excludeSubtreeTags()}
     *
     * Please look into {@see \Neos\Neos\Domain\SubtreeTagging\NeosVisibilityConstraints} instead.
     *
     * @deprecated with Neos 9 beta 19. To be removed with Neos 10.
     */
    public static function default(): self
    {
        return self::excludeSubtreeTags(SubtreeTags::create(SubtreeTag::disabled(), SubtreeTag::fromString('removed')));
    }

    /**
     * Legacy, only for Neos.Neos context!, for standalone use please use {@see self::createEmpty()}
     *
     * A subgraph without constraints for finding all nodes without filtering
     *
     * Nodes for example with tag disabled will be findable but not soft removed nodes
     *
     * For backwards compatibility to previous betas this method does not do what it promises.
     * The classic Neos backend use-case previously used this method to be able to find also disabled nodes.
     * Now with the introduction of soft removed tags, empty constraints will cause nodes
     * to show up that were previously non-existent. Thus, this factory restricts 'removed' after all.
     *
     * Please use {@see \Neos\Neos\Domain\SubtreeTagging\NeosVisibilityConstraints::excludeRemoved()} instead.
     *
     * @deprecated with Neos 9 beta 19. To be removed with Neos 10.
     */
    public static function withoutRestrictions(): self
    {
        return self::excludeSubtreeTags(SubtreeTags::fromStrings('removed'));
    }
}
