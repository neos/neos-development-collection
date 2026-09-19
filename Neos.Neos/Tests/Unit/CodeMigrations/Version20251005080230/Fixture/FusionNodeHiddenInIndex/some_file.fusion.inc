prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.hiddenInIndex || documentNode.hiddenInIndex || site.hiddenInIndex || q(node).property('_hiddenInIndex') || q(documentNode).property("_hiddenInIndex")}

    renderer = afx`
      <input
        type="checkbox"
        name={node.hiddenInIndex}
        value={someOtherVariable.hiddenInIndex}
        checked={props.checked}
        {...node.hiddenInIndex}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 15: You may need to rewrite "VARIABLE.hiddenInIndex" to VARIABLE.property('hiddenInMenu'). We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.property('hiddenInMenu') || documentNode.property('hiddenInMenu') || site.property('hiddenInMenu') || q(node).property('hiddenInMenu') || q(documentNode).property('hiddenInMenu')}

    renderer = afx`
      <input
        type="checkbox"
        name={node.property('hiddenInMenu')}
        value={someOtherVariable.hiddenInIndex}
        checked={props.checked}
        {...node.property('hiddenInMenu')}
      />
    `
  }
}
