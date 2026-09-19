prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {
  renderer = Neos.Fusion:Component {
    attributes = ${node.context.currentSite.siteResourcesPackageKey || site.context.currentSite.siteResourcesPackageKey || documentNode.context.currentSite.siteResourcesPackageKey}
    renderer = afx`
      <input
        name={node.context.currentSite.siteResourcesPackageKey}
        value={someOtherVariable.context.currentSite.siteResourcesPackageKey}
        {...node.context.currentSite.siteResourcesPackageKey}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 8: You very likely need to rewrite "VARIABLE.context.currentSite" to "Neos.Site.findBySiteNode(site)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {
  renderer = Neos.Fusion:Component {
    attributes = ${Neos.Site.findBySiteNode(site).siteResourcesPackageKey || Neos.Site.findBySiteNode(site).siteResourcesPackageKey || Neos.Site.findBySiteNode(site).siteResourcesPackageKey}
    renderer = afx`
      <input
        name={Neos.Site.findBySiteNode(site).siteResourcesPackageKey}
        value={someOtherVariable.context.currentSite.siteResourcesPackageKey}
        {...Neos.Site.findBySiteNode(site).siteResourcesPackageKey}
      />
    `
  }
}
