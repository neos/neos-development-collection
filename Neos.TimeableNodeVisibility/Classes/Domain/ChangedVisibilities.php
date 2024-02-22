<?php

namespace Neos\TimeableNodeVisibility\Domain;

/**
 * @internal
 */
class ChangedVisibilities
{
    /**
     * @var ChangedVisibility[]
     */
    private array $results;

    public function __construct(ChangedVisibility ...$results)
    {
        $this->results = $results;
    }

    public function countByType(ChangedVisibilityType $result): int
    {
        return count($this->getByType($result));
    }

    /**
     * @return array<ChangedVisibility>
     */
    public function getByType(ChangedVisibilityType $result): array
    {
        return array_filter($this->results, fn ($handlingResult) => $handlingResult->type === $result);
    }
}
