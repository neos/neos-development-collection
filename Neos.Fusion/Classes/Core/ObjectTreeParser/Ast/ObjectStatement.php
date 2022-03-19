<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class ObjectStatement extends AbstractStatement
{
    public function __construct(
        /** @psalm-readonly */
        public ObjectPath $path,
        /** @psalm-readonly */
        public ValueAssignment|ValueCopy|ValueUnset|null $operation,
        /** @psalm-readonly */
        public ?Block $block,
        /** @psalm-readonly */
        public int $cursor
    ) {
    }

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitObjectStatement($this, ...$args);
    }
}
