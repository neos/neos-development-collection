<?php
declare(strict_types=1);

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitorInterface;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
abstract class AbstractNode
{
    abstract public function visit(AstNodeVisitorInterface $visitor, ...$args);
}
