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

    public function add(HandlingResult $handlingResult)
    {
        $this->handlingResults[] = $handlingResult;
    }

    public function countByResult(string $result)
    {
        return count(array_filter($this->handlingResults, fn($handlingResult) => $handlingResult->result === $result));
    }

    public function getByResult(string $result)
    {
        return array_filter($this->handlingResults, fn($handlingResult) => $handlingResult->result === $result);
    }
}