<?php

declare(strict_types=1);

namespace Neos\Workspace\Ui\ViewModel;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class Sorting implements \JsonSerializable, ProtectedContextAwareInterface
{
    public function __construct(
        /**
         * @phpstan-var 'title'
         */
        public string $sortBy,
        public bool $sortAscending
    ) {
        if (!in_array($sortBy, ['title'], true)) {
            throw new \RuntimeException(sprintf('Invalid sortBy %s specified', $sortBy), 1736344550);
        }
    }

    /**
     * @param array<mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            sortBy: $array['sortBy'],
            sortAscending: (bool)$array['sortAscending'],
        );
    }

    public function withInvertedSorting(): self
    {
        return new self(
            sortBy: $this->sortBy,
            sortAscending: !$this->sortAscending
        );
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }

    public function allowsCallOfMethod($methodName)
    {
        return in_array($methodName, ['withInvertedSorting', 'jsonSerialize'], true);
    }
}
