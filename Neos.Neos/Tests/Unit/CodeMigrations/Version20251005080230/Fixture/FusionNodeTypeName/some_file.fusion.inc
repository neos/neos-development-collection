prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

renderer = Neos.Fusion:Component {

#
# pass down props
#
attributes = ${node.nodeType.name || documentNode.nodeType.name}
renderer = afx`
<input
        name={node.nodeType.name}
        value={someOtherVariable.nodeType.name}
        {...node.nodeType.name}
/>
`
}
}
-----
// TODO Neos.Neos-20251005080230 Line 14: You very likely need to rewrite "VARIABLE.nodeType" to "Neos.Node.nodeType(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
// TODO Neos.Neos-20251005080230 Line 14: You may need to rewrite "VARIABLE.nodeType.name" to "VARIABLE.nodeTypeName". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

renderer = Neos.Fusion:Component {

#
# pass down props
#
attributes = ${node.nodeTypeName || documentNode.nodeTypeName}
renderer = afx`
<input
        name={node.nodeTypeName}
        value={someOtherVariable.nodeType.name}
        {...node.nodeTypeName}
/>
`
}
}
