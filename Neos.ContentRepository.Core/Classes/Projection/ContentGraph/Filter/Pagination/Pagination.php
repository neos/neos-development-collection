<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;

/**
 * Pagination filter criteria for the {@see ContentSubgraphInterface} API
 *
 * @api
 */
final class Pagination
{
    private function __construct(
        public readonly int $limit,
        public readonly int $offset,
    ) {
        if ($this->limit < 1) {
            throw new \InvalidArgumentException(sprintf('Limit must not be less than 1, given: %d', $this->limit), 1680195505);
        }
        if ($this->offset < 0) {
            throw new \InvalidArgumentException(sprintf('Offset must not be a negative number, given: %d', $this->offset), 1680195530);
        }
    }

    public static function fromLimitAndOffset(int $limit, int $offset): self
    {
        return new self($limit, $offset);
    }

    /**
     * @param array<string, mixed> $array
     */
    public static function fromArray(array $array): self
    {
        $limit = null;
        $offset = null;
        if (isset($array['limit'])) {
            if (!is_numeric($array['limit'])) {
                throw new \InvalidArgumentException(sprintf('Limit must be an number or a numeric string, given: %s', get_debug_type($array['limit'])), 1680259067);
            }
            $limit = (int)$array['limit'];
            unset($array['limit']);
        }
        if (isset($array['offset'])) {
            if (!is_numeric($array['offset'])) {
                throw new \InvalidArgumentException(sprintf('Offset must be an number or a numeric string, given: %s', get_debug_type($array['offset'])), 1680259295);
            }
            $offset = (int)$array['offset'];
            unset($array['offset']);
        }
        if ($array !== []) {
            throw new \InvalidArgumentException(sprintf('Unsupported pagination array key%s: "%s"', count($array) === 1 ? '' : 's', implode('", "', array_keys($array))), 1680259558);
        }
        $limit = $limit ?? PHP_INT_MAX;
        $offset = $offset ?? 0;
        return new self($limit, $offset);
    }
}
