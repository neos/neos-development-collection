prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Component) {
  # label as a prop name is accepted
  label = ""
  node = ${node.label || documentNode.label || site.label}
  otherVariable = ${someOtherVariable.label}
  inAfx = afx`<Neos.Fusion:Value
      value={node.label}
      other={props.label}
  />`
}
-----
// TODO Neos.Neos-20251005080230 Line 6: You very likely need to rewrite "VARIABLE.label" to "Neos.Node.label(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Component) {
  # label as a prop name is accepted
  label = ""
  node = ${Neos.Node.label(node) || Neos.Node.label(documentNode) || Neos.Node.label(site)}
  otherVariable = ${someOtherVariable.label}
  inAfx = afx`<Neos.Fusion:Value
      value={Neos.Node.label(node)}
      other={props.label}
  />`
}
