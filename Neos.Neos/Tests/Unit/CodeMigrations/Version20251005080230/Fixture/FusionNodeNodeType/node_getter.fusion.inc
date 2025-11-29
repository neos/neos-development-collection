prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${node.nodeType || documentNode.nodeType || site.nodeType}
  otherVariable = ${someOtherVariable.nodeType}
  inAfx = afx`<Neos.Fusion:Value value={node.nodeType}/>`
  dontReplaceThis = ${node.nodeTypeName}
}
-----
// TODO Neos.Neos-20251005080230 Line 4: You very likely need to rewrite "VARIABLE.nodeType" to "Neos.Node.nodeType(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${Neos.Node.nodeType(node) || Neos.Node.nodeType(documentNode) || Neos.Node.nodeType(site)}
  otherVariable = ${someOtherVariable.nodeType}
  inAfx = afx`<Neos.Fusion:Value value={Neos.Node.nodeType(node)}/>`
  dontReplaceThis = ${node.nodeTypeName}
}
