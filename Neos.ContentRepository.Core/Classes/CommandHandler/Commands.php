<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\Subscription\Exception\CatchUpHadErrors;

/**
 * @api can be used as collection of commands to be individually handled:
 *
 *     foreach ($commands as $command) {
 *         $contentRepository->handle($command);
 *     }
 *
 * Note that as they are separate commands, they might individually fail due to constraints
 * or a projection or catchup failing during the first catchup with {@see CatchUpHadErrors}
 *
 * @implements \IteratorAggregate<int,CommandInterface>
 */
final readonly class Commands implements \IteratorAggregate, \Countable
{
    /** @var array<int,CommandInterface> */
    private array $items;

    private function __construct(
        CommandInterface ...$items
    ) {
        $this->items = array_values($items);
    }

    public static function create(CommandInterface ...$items): self
    {
        return new self(...$items);
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    /** @param array<CommandInterface> $array */
    public static function fromArray(array $array): self
    {
        return new self(...$array);
    }

    public function append(CommandInterface $command): self
    {
        return new self(...[...$this->items, $command]);
    }

    public function merge(self $other): self
    {
        return new self(...$this->items, ...$other->items);
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
