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

namespace Neos\ContentRepository\Core\Feature\NodeMove\Dto;

/**
 * The relation distribution strategy for node aggregates as defined in the NodeType declaration
 * Used for building relations to other node aggregates
 *
 * - `scatter` means that different nodes within the aggregate may be related to different other aggregates
 *      (e.g. parent).
 *      Still, specializations pointing to the same node using the fallback mechanism will be kept gathered.
 * - `gatherAll` means that all nodes within the aggregate must be related to the same other aggregate (e.g. parent)
 * - `gatherSpecializations` means that when a node is related to another node aggregate (e.g. parent),
 *      all specializations of that node will be related to that same aggregate while generalizations
 *      may be related to others
 *
 * @api DTO of {@see MoveNodeAggregate} command
 */
enum RelationDistributionStrategy: string implements \JsonSerializable
{
    case STRATEGY_SCATTER = 'scatter';
    case STRATEGY_GATHER_ALL = 'gatherAll';
    case STRATEGY_GATHER_SPECIALIZATIONS = 'gatherSpecializations';

    public static function fromString(?string $serialization): self
    {
        return !is_null($serialization)
            ? self::from($serialization)
            : self::STRATEGY_GATHER_ALL;
    }

    public static function fromName(string $name): self
    {
        if (str_starts_with($name, 'RelationDistributionStrategy::')) {
            $name = substr($name, strpos($name, '::') + 2);
            foreach (self::cases() as $status) {
                if ($name === $status->name) {
                    return $status;
                }
            }
        }
        return self::STRATEGY_GATHER_ALL;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
