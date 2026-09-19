prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {
  renderer = Neos.Fusion:Component {
    attributes = ${node.context.currentSite.siteResourcesPackageKey || site.context.currentSite.siteResourcesPackageKey || documentNode.context.currentSite.siteResourcesPackageKey}
    site = ${node.context.currentSite || site.context.currentSite || documentNode.context.currentSite}
    currentSiteNode = ${node.context.currentSiteNode}
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 6: !! node.context.currentSiteNode is removed in Neos 9.0. Check if you can't simply use ${site}.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {
  renderer = Neos.Fusion:Component {
    attributes = ${Neos.Site.findBySiteNode(site).siteResourcesPackageKey || Neos.Site.findBySiteNode(site).siteResourcesPackageKey || Neos.Site.findBySiteNode(site).siteResourcesPackageKey}
    site = ${Neos.Site.findBySiteNode(site) || Neos.Site.findBySiteNode(site) || Neos.Site.findBySiteNode(site)}
    currentSiteNode = ${node.context.currentSiteNode}
  }
}
