prototype(My.Namespace:Component)  < prototype(Neos.Fusion:Component) {

    isHomepage1 = ${documentNode == site}
    isHomepage2 = ${site == documentNode}
    isHomepage3 = ${node == site}
    isHomepage4 = ${site == node}

    isDocument1 = ${node == documentNode}
    isDocument2 = ${documentNode == node}

    isNotHomepage1 = ${documentNode != site}
    isNotHomepage2 = ${site != documentNode}

    complex = ${foo == 'bar' || documentNode == site && foo == 'bar'}

    nonMatching1 = ${mydocumentNode == siteNode}
    nonMatching2 = ${documentNode == siteNode}
    nonMatching3 = ${mydocumentNode == site}
    nonMatching4 = ${foo == 'bar' || documentNode == siteNode && foo == 'bar'}
    nonMatching5 = ${foo == 'bar' || mydocumentNode == site && foo == 'bar'}

}
-----
prototype(My.Namespace:Component)  < prototype(Neos.Fusion:Component) {

    isHomepage1 = ${documentNode.aggregateId.value == site.aggregateId.value}
    isHomepage2 = ${site.aggregateId.value == documentNode.aggregateId.value}
    isHomepage3 = ${node.aggregateId.value == site.aggregateId.value}
    isHomepage4 = ${site.aggregateId.value == node.aggregateId.value}

    isDocument1 = ${node.aggregateId.value == documentNode.aggregateId.value}
    isDocument2 = ${documentNode.aggregateId.value == node.aggregateId.value}

    isNotHomepage1 = ${documentNode.aggregateId.value != site.aggregateId.value}
    isNotHomepage2 = ${site.aggregateId.value != documentNode.aggregateId.value}

    complex = ${foo == 'bar' || documentNode.aggregateId.value == site.aggregateId.value && foo == 'bar'}

    nonMatching1 = ${mydocumentNode == siteNode}
    nonMatching2 = ${documentNode == siteNode}
    nonMatching3 = ${mydocumentNode == site}
    nonMatching4 = ${foo == 'bar' || documentNode == siteNode && foo == 'bar'}
    nonMatching5 = ${foo == 'bar' || mydocumentNode == site && foo == 'bar'}

}
