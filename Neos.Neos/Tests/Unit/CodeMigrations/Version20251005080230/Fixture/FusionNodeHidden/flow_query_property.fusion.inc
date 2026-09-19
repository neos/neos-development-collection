prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${q(node).property('_hidden') || q(documentNode).property("_hidden") || q(site).property("_hidden")}
  otherVariable = ${q(someOtherVariable).property('_hidden')}
  flowQuery = ${q(someOtherVariable).first().property('_hidden')}
  inAfx = afx`<Neos.Fusion:Value value={q(node).property('_hidden')}/>`
}
-----
// TODO Neos.Neos-20251005080230 Line 5: You may need to rewrite "q(VARIABLE).property('_hidden')" to Neos.Node.isDisabled(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Neos:Test)  < prototype(Neos.Fusion:Value) {
  node = ${Neos.Node.isDisabled(node) || Neos.Node.isDisabled(documentNode) || Neos.Node.isDisabled(site)}
  otherVariable = ${Neos.Node.isDisabled(someOtherVariable)}
  flowQuery = ${q(someOtherVariable).first().property('_hidden')}
  inAfx = afx`<Neos.Fusion:Value value={Neos.Node.isDisabled(node)}/>`
}
