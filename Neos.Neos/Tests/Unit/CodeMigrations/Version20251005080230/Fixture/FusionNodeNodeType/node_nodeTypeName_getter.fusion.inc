prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${node.nodeType.name || documentNode.nodeType.name || site.nodeType.name}
  otherVariable = ${someOtherVariable.nodeType.name}
  inAfx = afx`<Neos.Fusion:Value value={node.nodeType.name}/>`
}
-----
// TODO Neos.Neos-20251005080230 Line 5: You very likely need to rewrite "VARIABLE.nodeType" to "Neos.Node.nodeType(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
// TODO Neos.Neos-20251005080230 Line 5: You may need to rewrite "VARIABLE.nodeType.name" to "VARIABLE.nodeTypeName". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${node.nodeTypeName || documentNode.nodeTypeName || site.nodeTypeName}
  otherVariable = ${someOtherVariable.nodeType.name}
  inAfx = afx`<Neos.Fusion:Value value={node.nodeTypeName}/>`
}
