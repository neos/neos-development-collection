<?php

use Neos\ContentRepository\Projection\ContentGraph\Node;

class SomeClass
{
    public function run(Node $node)
    {
        return $node->isHidden();
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
        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryIdentifier);
        $nodeHiddenStateFinder = $contentRepository->getProjection(\Neos\ContentRepository\Projection\NodeHiddenState\NodeHiddenStateProjection::class);
        $hiddenState = $nodeHiddenStateFinder->findHiddenState($node->subgraphIdentity->contentStreamIdentifier, $node->subgraphIdentity->dimensionSpacePoint, $node->nodeAggregateIdentifier);
        return $hiddenState->isHidden();
    }
}

?>
