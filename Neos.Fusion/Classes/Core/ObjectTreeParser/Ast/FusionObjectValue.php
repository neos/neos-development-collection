<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

#[Flow\Proxy(false)]
class FusionObjectValue extends AbstractPathValue
{
    public function __construct(
        /** @psalm-readonly */
        public string $value
    ) {
    }

    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        return $visitor->visitFusionObjectValue($this, ...$args);
    }
}
