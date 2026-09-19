prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.hiddenAfterDateTime || documentNode.hiddenAfterDateTime}
    attributes2 = ${q(node).property("_hiddenAfterDateTime")}

    renderer = afx`
      <input
        type="checkbox"
        name={node.hiddenAfterDateTime}
        value={someOtherVariable.hiddenAfterDateTime}
        checked={props.checked}
        {...node.hiddenAfterDateTime}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 16: You may need to rewrite "VARIABLE.hiddenAfterDateTime" to q(VARIABLE).property("disableAfterDateTime"). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${q(node).property("disableAfterDateTime") || q(documentNode).property("disableAfterDateTime")}
    attributes2 = ${q(node).property("disableAfterDateTime")}

    renderer = afx`
      <input
        type="checkbox"
        name={q(node).property("disableAfterDateTime")}
        value={someOtherVariable.hiddenAfterDateTime}
        checked={props.checked}
        {...q(node).property("disableAfterDateTime")}
      />
    `
  }
}
