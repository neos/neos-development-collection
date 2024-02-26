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

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitorInterface;

#[Flow\Proxy(false)]
class StatementList extends AbstractNode
{
    /**
     * @psalm-readonly
     * @var AbstractStatement[]
     */
    public $statements = [];

    public function __construct(AbstractStatement ...$statements)
    {
        $this->statements = $statements;
    }

    public function visit(AstNodeVisitorInterface $visitor, mixed ...$args)
    {
        return $visitor->visitStatementList($this, ...$args);
    }
}
