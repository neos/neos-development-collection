<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Query;

use Flowpack\QueryObjectBuilder\MySQL\Builder\IdentExp;
use Flowpack\QueryObjectBuilder\MySQL\Q;

final readonly class Alias
{
    public function __construct(
        private string $value
    ) {
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public static function none(): self
    {
        return new self('');
    }

    public function resolve(IdentExp $identifier): IdentExp
    {
        if ($this->value === '') {
            return $identifier;
        }

        return Q::n($this->value . '.' . $identifier->ident());
    }
}
