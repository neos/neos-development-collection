<?php

namespace Neos\TimeableNodeVisibility\Domain;

class HandlingResults
{
    /**
     * @var HandlingResult[]
     */
    private array $handlingResults;

    public function __construct(HandlingResult ...$handlingResults)
    {
        $this->handlingResults = $handlingResults;
    }

    public function countByResult(HandlingResultType $result): int
    {
        return count($this->getByResult($result));
    }

    /**
     * @return array<HandlingResult>
     */
    public function getByResult(HandlingResultType $result): array
    {
        return array_filter($this->handlingResults, fn ($handlingResult) => $handlingResult->type === $result);
    }
}
