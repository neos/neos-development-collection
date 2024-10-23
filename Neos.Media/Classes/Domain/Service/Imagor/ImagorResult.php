<?php

declare(strict_types=1);

namespace Neos\Media\Domain\Service\Imagor;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class ImagorResult implements \ArrayAccess
{

    private function __construct(private readonly ?ImagorPathBuilder $builder) {
    }

    public static function empty(): self
    {
        return new self(null);
    }

    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, ['src', 'width', 'height']);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->builder) {
            // Case for ImagorResult::empty().
            return null;
        }


        if ($offset === 'src') {
            $this->builder->build();
        }
        // TODO: Lazy width / height
        return null;
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException('writing not supported!');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException('writing not supported!');
    }
}
