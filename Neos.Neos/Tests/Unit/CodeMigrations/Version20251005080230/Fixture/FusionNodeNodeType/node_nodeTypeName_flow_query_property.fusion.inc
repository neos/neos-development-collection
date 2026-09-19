prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${q(node).property('_nodeType.name') || q(documentNode).property("_nodeType.name") || q(site).property("_nodeType.name")}
  otherVariable = ${q(someOtherVariable).property('_nodeType.name')}
  inAfx = afx`<Neos.Fusion:Value value={q(node).property('_nodeType.name')}/>`
  dontReplaceThis = ${q(node).property('_nodeTypesname')}
}
-----
prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${node.nodeTypeName || documentNode.nodeTypeName || site.nodeTypeName}
  otherVariable = ${someOtherVariable.nodeTypeName}
  inAfx = afx`<Neos.Fusion:Value value={node.nodeTypeName}/>`
  dontReplaceThis = ${q(node).property('_nodeTypesname')}
}
