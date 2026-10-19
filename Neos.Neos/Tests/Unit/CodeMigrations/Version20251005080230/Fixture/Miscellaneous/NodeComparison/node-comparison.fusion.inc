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

    isHomepage1 = ${Neos.Node.equals(documentNode, site)}
    isHomepage2 = ${Neos.Node.equals(site, documentNode)}
    isHomepage3 = ${Neos.Node.equals(node, site)}
    isHomepage4 = ${Neos.Node.equals(site, node)}

    isDocument1 = ${Neos.Node.equals(node, documentNode)}
    isDocument2 = ${Neos.Node.equals(documentNode, node)}

    isNotHomepage1 = ${!Neos.Node.equals(documentNode, site)}
    isNotHomepage2 = ${!Neos.Node.equals(site, documentNode)}

    complex = ${foo == 'bar' || Neos.Node.equals(documentNode, site) && foo == 'bar'}

    nonMatching1 = ${mydocumentNode == siteNode}
    nonMatching2 = ${documentNode == siteNode}
    nonMatching3 = ${mydocumentNode == site}
    nonMatching4 = ${foo == 'bar' || documentNode == siteNode && foo == 'bar'}
    nonMatching5 = ${foo == 'bar' || mydocumentNode == site && foo == 'bar'}

}
