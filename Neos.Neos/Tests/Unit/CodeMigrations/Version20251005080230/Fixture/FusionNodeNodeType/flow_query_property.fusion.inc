prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${q(node).property('_nodeType') || q(documentNode).property("_nodeType") || q(site).property("_nodeType")}
  otherVariable = ${q(someOtherVariable).property('_nodeType')}
  nested = ${q(someOtherVariable).property('_nodeType.properties')}
  deepNested = ${q(someOtherVariable).property('_nodeType.options.myOption')}
  inAfx = afx`<Neos.Fusion:Value value={q(node).property('_nodeType')}/>`
}
-----
prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${Neos.Node.nodeType(node) || Neos.Node.nodeType(documentNode) || Neos.Node.nodeType(site)}
  otherVariable = ${Neos.Node.nodeType(someOtherVariable)}
  nested = ${Neos.Node.nodeType(someOtherVariable).properties}
  deepNested = ${Neos.Node.nodeType(someOtherVariable).options.myOption}
  inAfx = afx`<Neos.Fusion:Value value={Neos.Node.nodeType(node)}/>`
}
