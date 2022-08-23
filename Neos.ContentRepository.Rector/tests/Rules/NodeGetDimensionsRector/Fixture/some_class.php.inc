<?php

use Neos\ContentRepository\Projection\ContentGraph\Node;

class SomeClass
{
    public function run(Node $node)
    {
        return $node->getDimensions();
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
        // TODO 9.0 migration: Try to remove the toLegacyDimensionArray() call and make your codebase more typesafe.

        return $node->originDimensionSpacePoint->toLegacyDimensionArray();
    }
}

?>
