prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${this.node.depth}

    depth = ${node.depth || documentNode.depth}

    renderer = afx`
      <input
        type="checkbox"
        name={node.depth}
        value={someOtherVariable.depth}
        checked={props.checked}
        {...node.depth}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 10: You may need to rewrite "VARIABLE.depth" to Neos.Node.depth(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
// TODO Neos.Neos-20251005080230 Line 18: You may need to rewrite "VARIABLE.depth" to Neos.Node.depth(VARIABLE). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${this.node.depth}

    depth = ${Neos.Node.depth(node) || Neos.Node.depth(documentNode)}

    renderer = afx`
      <input
        type="checkbox"
        name={Neos.Node.depth(node)}
        value={someOtherVariable.depth}
        checked={props.checked}
        {...Neos.Node.depth(node)}
      />
    `
  }
}
