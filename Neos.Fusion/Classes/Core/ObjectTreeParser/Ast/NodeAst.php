<?php

namespace Neos\Fusion\Core\ObjectTreeParser\Ast;

use Neos\Fusion\Core\ObjectTreeParser\AstNodeVisitor;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
abstract class NodeAst
{
    public function visit(AstNodeVisitor $visitor, ...$args)
    {
        static $visitorMethod;
        if (isset($visitorMethod) === false) {
            $classWithoutNamespace = basename(str_replace('\\', '/', static::class));
            $visitorMethod = 'visit' . $classWithoutNamespace;
        }
        return $visitor->$visitorMethod($this, ...$args);
    }

    public function __call(string $name, array $arguments)
    {
        $prop = lcfirst(preg_replace('{^get}', '', $name));
        return $this->$prop;
    }
}
