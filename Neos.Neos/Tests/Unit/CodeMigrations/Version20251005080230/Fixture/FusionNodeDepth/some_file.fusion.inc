prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.depth || documentNode.depth || q(node).property('_depth') || q(documentNode).property("_depth")}
    foo = ${q(bar).property('_depth') || q(bar).property("_depth")}
    boo = ${q(nodes).first().property('_depth') || q(nodes).first().property("_depth")}

    renderer = afx`
      <input
        type="checkbox"
        name={node.depth}
        value={someOtherVariable.depth || something}
        path={someOtherVariable.depth}
        checked={props.checked}
        {...node.depth}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 19: You may need to rewrite "VARIABLE.depth" to Neos.Node.depth(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
// TODO Neos.Neos-20251005080230 Line 20: You may need to rewrite "VARIABLE.depth" to Neos.Node.depth(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
// TODO Neos.Neos-20251005080230 Line 13: !! You very likely need to rewrite "q(VARIABLE).property("_depth")" to "Neos.Node.depth(VARIABLE)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${Neos.Node.depth(node) || Neos.Node.depth(documentNode) || Neos.Node.depth(node) || Neos.Node.depth(documentNode)}
    foo = ${Neos.Node.depth(bar) || Neos.Node.depth(bar)}
    boo = ${q(nodes).first().property('_depth') || q(nodes).first().property("_depth")}

    renderer = afx`
      <input
        type="checkbox"
        name={Neos.Node.depth(node)}
        value={someOtherVariable.depth || something}
        path={someOtherVariable.depth}
        checked={props.checked}
        {...Neos.Node.depth(node)}
      />
    `
  }
}
