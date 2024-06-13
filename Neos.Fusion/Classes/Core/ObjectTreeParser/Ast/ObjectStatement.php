<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitorInterface;

/** @internal */
#[Flow\Proxy(false)]
final readonly class ObjectStatement extends AbstractStatement
{
    public function __construct(
        public ObjectPath $path,
        public ValueAssignment|ValueCopy|ValueUnset|null $operation,
        public ?Block $block,
        public int $cursor
    ) {
    }

    public function visit(AstNodeVisitorInterface $visitor, mixed ...$args)
    {
        return $visitor->visitObjectStatement($this, ...$args);
    }
}
