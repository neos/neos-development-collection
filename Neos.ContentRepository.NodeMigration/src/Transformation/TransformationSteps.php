<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

/**
 * @implements \IteratorAggregate<TransformationStep>
 */
final readonly class TransformationSteps implements \IteratorAggregate, \Countable
{
    /** @var array<TransformationStep> */
    private array $items;

    private function __construct(
        TransformationStep ...$items
    ) {
        $notEmpty = [];
        foreach ($items as $item) {
            if (!$item->commands->isEmpty()) {
                $notEmpty[] = $item;
            }
        }
        $this->items = $notEmpty;
    }

    public static function create(TransformationStep ...$items): self
    {
        return new self(...$items);
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function merge(TransformationSteps $other): self
    {
        return new self(...[...$this->items, ...$other->items]);
    }

    public function withAppended(TransformationStep $transformationStep): self
    {
        return new self(...[...$this->items, $transformationStep]);
    }

    public function filterConfirmationRequired(): self
    {
        return new self(...array_filter($this->items, fn (TransformationStep $step) => $step->requireConfirmation));
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }
}
