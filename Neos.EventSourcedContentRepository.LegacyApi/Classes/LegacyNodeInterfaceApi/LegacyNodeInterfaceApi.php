<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\LegacyApi\LegacyNodeInterfaceApi;

interface LegacyNodeInterfaceApi
{
    public function getIdentifier(): int;

    public function getContextPath(): string;

    public function getDepth(): int;

    public function getHiddenBeforeDateTime(): ?\DateTimeInterface;

    public function getHiddenAfterDateTime(): ?\DateTimeInterface;
}
