prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${node.context.inBackend || site.context.inBackend || documentNode.context.inBackend}

    renderer = afx`
      <input
        type="checkbox"
        name={node.context.inBackend}
        value={someOtherVariable.context.inBackend}
        checked={props.checked}
        {...node.context.inBackend}
      />
    `
  }
}
-----
// TODO Neos.Neos-20251005080230 Line 15: You very likely need to rewrite "VARIABLE.context.inBackend" to "renderingMode.isEdit". We did not auto-apply this migration because we cannot be sure whether the variable is a Node.
prototype(Neos.Fusion.Form:Checkbox)  < prototype(Neos.Fusion.Form:Component.Field) {

  renderer = Neos.Fusion:Component {

    #
    # pass down props
    #
    attributes = ${renderingMode.isEdit || renderingMode.isEdit || renderingMode.isEdit}

    renderer = afx`
      <input
        type="checkbox"
        name={renderingMode.isEdit}
        value={someOtherVariable.context.inBackend}
        checked={props.checked}
        {...renderingMode.isEdit}
      />
    `
  }
}
