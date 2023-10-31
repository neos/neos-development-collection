# Neos asset usages

Custom projection to effectively track the usage of assets within the Event Sourced Content Repository

### Neos usage strategy

This module comes with an implementation of the `\Neos\Media\Domain\Strategy\AssetUsageStrategyInterface` that
will protect used assets from being removed via the Neos Media module.

By default, the asset usage tracking is active for the `default` Content Repository.
You can activate it for additional Content Repositories via `Settings.yaml`:

```yaml
Neos:
  Neos:
    assetUsage:
      contentRepositories:
        'someContentRepository': true
```

You can also _disable_ asset usages for a Content Repository by setting the flag to `false`.

### Find usages

To find asset usages for a given Content Repository, the `AssetUsageFinder` can be used:

```php
$assetFilter = AssetUsageFilter::create()
   ->withContentStream($liveWorkspace->getCurrentContentStreamId())
   ->groupByAsset();
$usages = $contentRepository->projectionState(AssetUsageProjection::class)->findByFilter($assetFilter);

//$usages->count();
foreach ($usagesByContentRepository as $usage) {
    // $usage->assetId;
}
```

To look up asset usages for all configured Content Repositories, the `GlobalAssetUsageService` can be used instead:

```php
$usagesByContentRepository = $assetUsageService->findByFilter($assetFilter);

//$usagesByContentRepository->count();
foreach ($usagesByContentRepository as $contentRepositoryId => $usages) {
  foreach ($usages as $usage) {
    // $usage->assetId;
  }
}
```

## Further information

### Restrictions

In this version, usages are only removed if the corresponding node property is changed or the node itself is removed.
It won't remove usages from child nodes if a parent node is deleted because we don't keep track of the
full node hierarchy.

The `neos.neos.assetusage:assetusage:sync` command can be used to (re-)synchronize all assets:

    ./flow assetusage:sync 
