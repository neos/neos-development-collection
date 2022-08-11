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

namespace Neos\ContentRepository\Feature\RootNodeCreation\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * Create root node aggregate with node command
 *
 * A root node has no variants and no origin dimension space point but occupies the whole allowed dimension subspace.
 * It also has no tethered child nodes.
 */
final class CreateRootNodeAggregateWithNode implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly NodeTypeName $nodeTypeName,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    /**
     * @param array<string,string> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamIdentifier::fromString($array['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            NodeTypeName::fromString($array['nodeTypeName']),
            UserIdentifier::fromString($array['initiatingUserIdentifier'])
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'nodeTypeName' => $this->nodeTypeName,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
        ];
    }

    public function createCopyForContentStream(ContentStreamIdentifier $target): self
    {
        return new self(
            $target,
            $this->nodeAggregateIdentifier,
            $this->nodeTypeName,
            $this->initiatingUserIdentifier
        );
    }
}
