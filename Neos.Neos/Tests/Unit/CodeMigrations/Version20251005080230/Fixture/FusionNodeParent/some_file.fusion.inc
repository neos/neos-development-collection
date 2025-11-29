prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.parent || documentNode.parent}

    renderer = afx`
      <input
        type="checkbox"
        name={node.parent}
        value={someOtherVariable.parent}
        data-parents={q(node).parents()}
        checked={props.checked}
        {...node.parent}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 15: You may need to rewrite "VARIABLE.parent" to "q(VARIABLE).parent().get(0)". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${q(node).parent().get(0) || q(documentNode).parent().get(0)}

    renderer = afx`
      <input
        type="checkbox"
        name={q(node).parent().get(0)}
        value={someOtherVariable.parent}
        data-parents={q(node).parents()}
        checked={props.checked}
        {...q(node).parent().get(0)}
      />
    `
  }
}
