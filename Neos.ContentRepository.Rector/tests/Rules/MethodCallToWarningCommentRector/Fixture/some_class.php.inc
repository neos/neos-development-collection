<?php

use Neos\ContentRepository\Projection\ContentGraph\Node;

class SomeClass
{
    public function run(Node $node)
    {
        return $node->getWorkspace();
    }
}

?>
-----
<?php

use Neos\ContentRepository\Projection\ContentGraph\Node;

class SomeClass
{
    public function run(Node $node)
    {
        // TODO 9.0 migration: !! Node::getWorkspace() does not make sense anymore concept-wise. In Neos < 9, it pointed to the workspace where the node was *at home at*. Now, the closest we have here is the node identity.

        return $node->getWorkspace();
    }
}

?>
