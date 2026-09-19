prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.hiddenBeforeDateTime || documentNode.hiddenBeforeDateTime}
    attribute2 = ${q(node).property("_hiddenBeforeDateTime")}

    renderer = afx`
      <input
        type="checkbox"
        name={node.hiddenBeforeDateTime}
        value={someOtherVariable.hiddenBeforeDateTime}
        checked={props.checked}
        {...node.hiddenBeforeDateTime}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 16: You may need to rewrite "VARIABLE.hiddenBeforeDateTime" to q(VARIABLE).property("enableAfterDateTime"). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${q(node).property("enableAfterDateTime") || q(documentNode).property("enableAfterDateTime")}
    attribute2 = ${q(node).property("enableAfterDateTime")}

    renderer = afx`
      <input
        type="checkbox"
        name={q(node).property("enableAfterDateTime")}
        value={someOtherVariable.hiddenBeforeDateTime}
        checked={props.checked}
        {...q(node).property("enableAfterDateTime")}
      />
    `
  }
}
