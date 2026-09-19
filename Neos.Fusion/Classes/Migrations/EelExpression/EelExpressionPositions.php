<?php

namespace Neos\Fusion\Migrations\EelExpression;

use Neos\Fusion\Core\ObjectTreeParser\Ast\EelExpressionValue;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 * @internal
 */
final class EelExpressionPositions implements \IteratorAggregate
{
    /**
     * @var EelExpressionPosition[]
     */
    private array $elements;

    private function __construct(
        EelExpressionPosition ...$eelExpressionPositions
    ) {
        $this->elements = $eelExpressionPositions;
    }

    public static function fromArray(array $eelExpressionPositions): self
    {
        return new self(...$eelExpressionPositions);
    }

    /**
     * @return \Traversable<EelExpressionPosition>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->elements);
    }

    public function withOffset(int $offset): self
    {
        return $this->map(fn (EelExpressionPosition $expressionPosition) => $expressionPosition->withOffset($offset));
    }

    public function map(\Closure $mapFn): self
    {
        return new EelExpressionPositions(...array_map($mapFn, $this->elements));
    }

    public function addAndSort(EelExpressionPositions $other): self
    {
        $elements = array_merge($this->elements, $other->elements);
        usort($elements, fn (EelExpressionPosition $a, EelExpressionPosition $b) => $a->fromOffset <=> $b->fromOffset);

        return new self(...$elements);
    }

    public function isEmpty(): bool
    {
        return empty($this->elements);
    }

    public function first(): EelExpressionPosition
    {
        return reset($this->elements);
    }

    public function withoutFirst(): self
    {
        $elements = $this->elements;
        array_shift($elements);
        return new self(...$elements);
    }

    public function byEelExpressionValue(EelExpressionValue $eelExpressionValue): ?EelExpressionPosition
    {
        foreach ($this->elements as $element) {
            if ($element->eelExpressionValue === $eelExpressionValue) {
                return $element;
            }
        }
        return null;
    }
}
