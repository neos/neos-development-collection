prototype(My.Namespace:Value)  < prototype(Neos.Fusion:Value) {

    lifetime = ${q(node).cacheLifetime()}

    @cache.maximumLifetime = ${q(node).context({'invisibleContentShown': true}).find('[instanceof Neos.Neos:Content]').cacheLifetime()}

}
-----
// TODO Neos.Neos-20251005080230 Line 5: You may need to remove ".cacheLifetime()" as this FlowQuery Operation has been removed. This is not needed anymore as the concept of timeable node visibility has changed. See https://github.com/neos/timeable-node-visibility
// TODO Neos.Neos-20251005080230 Line 7: You may need to remove ".cacheLifetime()" as this FlowQuery Operation has been removed. This is not needed anymore as the concept of timeable node visibility has changed. See https://github.com/neos/timeable-node-visibility
prototype(My.Namespace:Value)  < prototype(Neos.Fusion:Value) {

    lifetime = ${q(node).cacheLifetime()}

    @cache.maximumLifetime = ${q(node).context({'invisibleContentShown': true}).find('[instanceof Neos.Neos:Content]').cacheLifetime()}

}
