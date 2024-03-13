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
 * The visibility constraints define a context in which the content graph is accessed.
 *
 * For example: In the `frontend` context, nodes with the `disabled` tag are excluded. In the `backend` context {@see self::withoutRestrictions()} they are included
 *
 * @api
 */
final readonly class VisibilityConstraints implements \JsonSerializable
{
    /**
     * @param SubtreeTags $tagConstraints A set of {@see SubtreeTag} instances that will be _excluded_ from the results of any content graph query
     */
    private function __construct(
        public SubtreeTags $tagConstraints,
    ) {
    }

    public function isDisabledContentShown(): bool
    {
        return $this->tagConstraints->contain(SubtreeTag::fromString('disabled'));
    }

    public function getHash(): string
    {
        return md5(implode('|', $this->tagConstraints->toStringArray()));
    }

    public static function withoutRestrictions(): self
    {
        return new self(SubtreeTags::createEmpty());
    }

    public static function frontend(): VisibilityConstraints
    {
        return new self(SubtreeTags::fromStrings('disabled'));
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
