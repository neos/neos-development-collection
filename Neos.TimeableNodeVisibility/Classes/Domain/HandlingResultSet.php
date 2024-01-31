<?php

namespace Neos\TimeableNodeVisibility\Domain;

class HandlingResultSet
{
    /**
     * @var HandlingResult[]
     */
    private array $handlingResults = [];

    public function __construct()
    {
    }

    public function add(HandlingResult $handlingResult): void
    {
        $this->handlingResults[] = $handlingResult;
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
