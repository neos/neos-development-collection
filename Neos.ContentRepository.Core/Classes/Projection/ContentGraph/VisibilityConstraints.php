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

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;

/**
 * The context parameters value object
 *
 * Maybe future: "Node Filter" tree or so as replacement of ReadNodePrivilege?
 *
 * @api
 */
final readonly class VisibilityConstraints implements \JsonSerializable
{
    private function __construct(
        public SubtreeTags $excludedTags,
    ) {
    }

    public function isDisabledContentShown(): bool
    {
        return $this->excludedTags->contain(SubtreeTag::fromString('disabled'));
    }

    public function getHash(): string
    {
        return md5(implode('|', $this->excludedTags->toStringArray()));
    }

    public static function withoutRestrictions(): self
    {
        return new self(SubtreeTags::createEmpty());
    }

    public static function frontend(): VisibilityConstraints
    {
        return new self(SubtreeTags::fromStrings('disabled'));
    }

    public function jsonSerialize(): string
    {
        return $this->getHash();
    }
}
