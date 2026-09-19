prototype(Neos.Context:Prototype)  < prototype(Neos.Fusion:Component) {

    currentSite = ${node.context.currentSite}
    currentSiteNode = ${node.context.currentSiteNode}
    currentDomain = ${node.context.currentDomain}

    isLive = ${node.context.isLive}
    isInBackend = ${node.context.isInBackend}

    preview = ${node.context.currentRenderingMode.preview}
    edit = ${node.context.currentRenderingMode.edit}
    name = ${node.context.currentRenderingMode.name}
    currentRenderingMode = ${node.context.currentRenderingMode}

    properties = ${node.context.properties}

}
-----
// TODO Neos.Neos-20251005080230 Line 19: !! node.context.properties is removed in Neos 9.0.
// TODO Neos.Neos-20251005080230 Line 9: !! node.context.currentDomain is removed in Neos 9.0.
// TODO Neos.Neos-20251005080230 Line 8: !! node.context.currentSiteNode is removed in Neos 9.0. Check if you can't simply use ${site}.
// TODO Neos.Neos-20251005080230 Line 17: You very likely need to rewrite "VARIABLE.context.currentRenderingMode..." to "renderingMode...". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Context:Prototype)  < prototype(Neos.Fusion:Component) {

    currentSite = ${Neos.Site.findBySiteNode(site)}
    currentSiteNode = ${node.context.currentSiteNode}
    currentDomain = ${node.context.currentDomain}

    isLive = ${node.context.isLive}
    isInBackend = ${node.context.isInBackend}

    preview = ${renderingMode.isPreview}
    edit = ${renderingMode.isEdit}
    name = ${renderingMode.name}
    currentRenderingMode = ${node.context.currentRenderingMode}

    properties = ${node.context.properties}

}
