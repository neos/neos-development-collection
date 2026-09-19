prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.path || documentNode.path || q(node).property('_path') || q(documentNode).property("_path")}
    foo = ${q(bar).property('_path') || q(bar).property("_path")}
    boo = ${q(nodes).first().property('_path') || q(nodes).first().property("_path")}

    renderer = afx`
      <input
        type="checkbox"
        name={node.path}
        value={someOtherVariable.path || something}
        path={someOtherVariable.path}
        checked={props.checked}
        {...node.path}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 19: You may need to rewrite "VARIABLE.path" to Neos.Node.path(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
// TODO Neos.Neos-20251005080230 Line 20: You may need to rewrite "VARIABLE.path" to Neos.Node.path(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
// TODO Neos.Neos-20251005080230 Line 13: !! You very likely need to rewrite "q(VARIABLE).property("_path")" to "Neos.Node.path(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${Neos.Node.path(node) || Neos.Node.path(documentNode) || Neos.Node.path(node) || Neos.Node.path(documentNode)}
    foo = ${Neos.Node.path(bar) || Neos.Node.path(bar)}
    boo = ${q(nodes).first().property('_path') || q(nodes).first().property("_path")}

    renderer = afx`
      <input
        type="checkbox"
        name={Neos.Node.path(node)}
        value={someOtherVariable.path || something}
        path={someOtherVariable.path}
        checked={props.checked}
        {...Neos.Node.path(node)}
      />
    `
  }
}
