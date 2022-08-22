<?php

namespace Rector\Tests\Neos\ContentRepository\Rector\Rector\MethodCall\NodeGetChildNodesRector\Fixture;

use Neos\ContentRepository\Projection\ContentGraph\Node;

class SomeClass
{
    public function run(Node $node)
    {
        foreach ($node->getChildNodes() as $node) {
        }
    }
}

?>
-----
<?php

namespace Rector\Tests\Neos\ContentRepository\Rector\Rector\MethodCall\NodeGetChildNodesRector\Fixture;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

class SomeClass
{

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function run(Node $node)
    {
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        foreach ($subgraph->findChildNodes($node->nodeAggregateIdentifier) as $node) {
        }
    }
}

?>
