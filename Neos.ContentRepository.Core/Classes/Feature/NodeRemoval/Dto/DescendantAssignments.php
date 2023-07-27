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

namespace Neos\ContentRepository\Core\Feature\NodeRemoval\Dto;

use Neos\Flow\Annotations as Flow;

/**
 * @api used as part of events
 */
#[Flow\Proxy(false)]
final class DescendantAssignments implements \JsonSerializable
{
    /**
     * @var array<int,DescendantAssignment>
     */
    public readonly array $assignments;

    public function __construct(DescendantAssignment ...$assignments)
    {
        $this->assignments = $assignments;
    }

    /**
     * @param array<int,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(...array_map(
            fn (array $assignmentData): DescendantAssignment => DescendantAssignment::fromArray($assignmentData),
            $array
        ));
    }

    /**
     * @return array<int,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->assignments;
    }
}
