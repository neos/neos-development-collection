<?php

use Neos\ContentRepository\Projection\ContentGraph\Node;

class SomeClass
{
    public function run(Node $node)
    {
        return $node->getPath();
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
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        // TODO 9.0 migration: Try to remove the (string) cast and make your code more type-safe.

        return (string) $subgraph->findNodePath($node->nodeAggregateIdentifier);
    }
}

?>
