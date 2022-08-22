<?php

use Neos\ContentRepository\Projection\ContentGraph\Node;

class SomeClass
{
    public function run(Node $node)
    {
        return $node->getContext()->getWorkspace();
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
        return $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamIdentifier($node->subgraphIdentity->contentStreamIdentifier);
    }
}

?>
