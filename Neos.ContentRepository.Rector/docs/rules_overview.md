# 4 Rules Overview

## InjectContentRepositoryRegistryIfNeededRector

add injection for `$contentRepositoryRegistry` if in use.

- class: [`Neos\ContentRepository\Rector\Rules\InjectContentRepositoryRegistryIfNeededRector`](../src/Rules/InjectContentRepositoryRegistryIfNeededRector.php)

```diff
 <?php

 use Neos\ContentRepository\Projection\ContentGraph\Node;

 class SomeClass
 {
+    #[Flow\Inject]
+    protected \Neos\ContentRepositoryRegistry\ContentRepositoryRegistry $contentRepositoryRegistry;
     public function run(Node $node)
     {
         $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
     }
 }

 ?>
```

<br>

## NodeGetChildNodesRector

`"NodeInterface::getChildNodes()"` will be rewritten

- class: [`Neos\ContentRepository\Rector\Rules\NodeGetChildNodesRector`](../src/Rules/NodeGetChildNodesRector.php)

```diff
 <?php

 use Neos\ContentRepository\Projection\ContentGraph\Node;

 class SomeClass
 {
     public function run(Node $node)
     {
-        foreach ($node->getChildNodes() as $node) {
+        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
+        // TODO 9.0 migration: Try to remove the iterator_to_array($nodes) call.
+
+        foreach (iterator_to_array($subgraph->findChildNodes($node->nodeAggregateIdentifier)) as $node) {
         }
     }
 }

 ?>
```

<br>

## NodeGetContextGetWorkspaceRector

`"NodeInterface::getContext()::getWorkspace()"` will be rewritten

- class: [`Neos\ContentRepository\Rector\Rules\NodeGetContextGetWorkspaceRector`](../src/Rules/NodeGetContextGetWorkspaceRector.php)

```diff
 <?php

 use Neos\ContentRepository\Projection\ContentGraph\Node;

 class SomeClass
 {
     public function run(Node $node)
     {
-        return $node->getContext()->getWorkspace();
+        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryIdentifier);
+        return $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamIdentifier($node->subgraphIdentity->contentStreamIdentifier);
     }
 }

 ?>
```

<br>

## NodeIsHiddenRector

`"NodeInterface::isHidden()"` will be rewritten

- class: [`Neos\ContentRepository\Rector\Rules\NodeIsHiddenRector`](../src/Rules/NodeIsHiddenRector.php)

```diff
 <?php

 use Neos\ContentRepository\Projection\ContentGraph\Node;

 class SomeClass
 {
     public function run(Node $node)
     {
-        return $node->isHidden();
+        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryIdentifier);
+        $nodeHiddenStateFinder = $contentRepository->getProjection(\Neos\ContentRepository\Projection\NodeHiddenState\NodeHiddenStateProjection::class);
+        $hiddenState = $nodeHiddenStateFinder->findHiddenState($node->subgraphIdentity->contentStreamIdentifier, $node->subgraphIdentity->dimensionSpacePoint, $node->nodeAggregateIdentifier);
+        return $hiddenState->isHidden();
     }
 }

 ?>
```

<br>
