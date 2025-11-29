prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

    title = ${q([node]).sort('title')}
    creationDateTime = ${q(node).children().sort("_creationDateTime")}
    lastModificationDateTime = ${q(node).children().sort('_lastModificationDateTime')}
    lastPublicationDateTime = ${q(node).children().sort('_lastPublicationDateTime', "ASC")}

    otherUnderscore = ${q(node).children().sort("_name", "ASC")}
}
-----
// TODO Neos.Neos-20251005080230 Line 8: The "sort()" FlowQuery operation for nodes does no longer support underscore properties. In case it was sorted by _creationDateTime, _lastModificationDateTime or _lastPublicationDateTime in neos 9.0 the new sortByTimestamp(created|lastModified|originalCreated|originalLastModified) flowquery can be used.
// TODO Neos.Neos-20251005080230 Line 9: The "sort()" FlowQuery operation for nodes does no longer support underscore properties. In case it was sorted by _creationDateTime, _lastModificationDateTime or _lastPublicationDateTime in neos 9.0 the new sortByTimestamp(created|lastModified|originalCreated|originalLastModified) flowquery can be used.
// TODO Neos.Neos-20251005080230 Line 10: The "sort()" FlowQuery operation for nodes does no longer support underscore properties. In case it was sorted by _creationDateTime, _lastModificationDateTime or _lastPublicationDateTime in neos 9.0 the new sortByTimestamp(created|lastModified|originalCreated|originalLastModified) flowquery can be used.
// TODO Neos.Neos-20251005080230 Line 12: The "sort()" FlowQuery operation for nodes does no longer support underscore properties. In case it was sorted by _creationDateTime, _lastModificationDateTime or _lastPublicationDateTime in neos 9.0 the new sortByTimestamp(created|lastModified|originalCreated|originalLastModified) flowquery can be used.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

    title = ${q([node]).sort('title')}
    creationDateTime = ${q(node).children().sort("_creationDateTime")}
    lastModificationDateTime = ${q(node).children().sort('_lastModificationDateTime')}
    lastPublicationDateTime = ${q(node).children().sort('_lastPublicationDateTime', "ASC")}

    otherUnderscore = ${q(node).children().sort("_name", "ASC")}
}
