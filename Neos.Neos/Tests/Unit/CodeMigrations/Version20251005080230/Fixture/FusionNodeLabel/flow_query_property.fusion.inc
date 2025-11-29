prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${q(node).property('_label') || q(documentNode).property("_label") || q(site).property("_label")}
  otherVariable = ${q(someOtherVariable).property('_label')}
  inAfx = afx`<Neos.Fusion:Value value={q(node).property('_label')}/>`
}
-----
prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${Neos.Node.label(node) || Neos.Node.label(documentNode) || Neos.Node.label(site)}
  otherVariable = ${Neos.Node.label(someOtherVariable)}
  inAfx = afx`<Neos.Fusion:Value value={Neos.Node.label(node)}/>`
}
